<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Security;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sends out an email for failed logins in TYPO3 Backend when a certain threshold of failed logins
 * during a certain timeframe has happened.
 *
 * Relevant settings:
 * $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr']
 *
 * @internal this class is not part of the TYPO3 Core API as this is a concrete hook implementation
 */
class FailedLoginAttemptNotification
{
    use LogDataTrait;

    /**
     * The receiver of the notification
     * @var string
     */
    protected $notificationRecipientEmailAddress;

    /**
     * Time span (in seconds) within the number of failed logins are collected.
     * Number of sections back in time to check. This is a kind of limit for how many failures an hour.
     * @var int
     */
    protected $warningPeriod;

    /**
     * The maximum accepted number of warnings before an email to $notificationRecipientEmailAddress is sent
     * @var int
     */
    protected $failedLoginAttemptsThreshold;

    public function __construct(string $notificationRecipientEmailAddress = null, int $warningPeriod = 3600, int $failedLoginAttemptsThreshold = 3)
    {
        $this->notificationRecipientEmailAddress = $notificationRecipientEmailAddress ?? (string)$GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        $this->warningPeriod = $warningPeriod;
        $this->failedLoginAttemptsThreshold = $failedLoginAttemptsThreshold;
    }

    /**
     * Sends a warning email if there has been a certain amount of failed logins during a period.
     * If a login fails, this function is called. It will look up the sys_log to see if there
     * have been more than $failedLoginAttemptsThreshold failed logins the last X seconds
     * (default 3600, see $warningPeriod). If so, an email with a warning is sent.
     *
     * @param array $params always empty in this hook
     * @param AbstractUserAuthentication $user the referenced user where the hook is called.
     * @return bool always returns true to ensure "sleep" functionality of AbstractUserAuthentication is kept.
     */
    public function sendEmailOnLoginFailures(array $params, AbstractUserAuthentication $user): bool
    {
        if (!($user instanceof BackendUserAuthentication)) {
            // This notification only works for backend users
            return true;
        }
        if (!GeneralUtility::validEmail($this->notificationRecipientEmailAddress)) {
            return true;
        }

        $earliestTimeToCheckForFailures = $GLOBALS['EXEC_TIME'] - $this->warningPeriod;
        $loginFailures = $this->getLoginFailures($earliestTimeToCheckForFailures);
        // Check for more than a maximum number of login failures with the last period
        if (count($loginFailures) > $this->failedLoginAttemptsThreshold) {
            // OK, so there were more than the max allowed number of login failures - so we will send an email then.
            $this->sendLoginAttemptEmail($loginFailures);
            // Login failure attempt written to log, which will be picked up later-on again
            $user->writelog(
                SystemLogType::LOGIN,
                SystemLogLoginAction::SEND_FAILURE_WARNING_EMAIL,
                SystemLogErrorClassification::MESSAGE,
                3,
                'Failure warning (%s failures within %s seconds) sent by email to %s',
                [count($loginFailures), $this->warningPeriod, $this->notificationRecipientEmailAddress]
            );
        }
        return true;
    }

    /**
     * Retrieves all failed logins within a given timeframe until now.
     *
     * @param int $earliestTimeToCheckForFailures A UNIX timestamp that acts as the "earliest" date to check within the logs
     * @return array a list of sys_log entries since the earliest, or empty if no entries have been logged
     */
    protected function getLoginFailures(int $earliestTimeToCheckForFailures): array
    {
        // Get last flag set in the log for sending an email
        // If a notification was e.g. sent 20mins ago, only check the entries of the last 20 minutes
        $queryBuilder = $this->createPreparedQueryBuilder($earliestTimeToCheckForFailures, SystemLogLoginAction::SEND_FAILURE_WARNING_EMAIL);
        $statement = $queryBuilder
            ->select('tstamp')
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults(1)
            ->executeQuery();
        if ($lastTimeANotificationWasSent = $statement->fetchOne()) {
            $earliestTimeToCheckForFailures = (int)$lastTimeANotificationWasSent;
        }
        $queryBuilder = $this->createPreparedQueryBuilder($earliestTimeToCheckForFailures, SystemLogLoginAction::ATTEMPT);
        $previousFailures = $queryBuilder
            ->select('*')
            ->orderBy('tstamp')
            ->executeQuery()
            ->fetchAllAssociative();
        return is_array($previousFailures) ? $previousFailures : [];
    }

    /**
     * Sends out an email if the number of attempts have exceeded a limit.
     *
     * @param array $previousFailures sys_log entries that have been logged since the last time a notification was sent
     */
    protected function sendLoginAttemptEmail(array $previousFailures): void
    {
        $emailData = [];
        foreach ($previousFailures as $row) {
            $text = $this->formatLogDetails($row['details'] ?? '', $row['log_data'] ?? '');
            if ((int)$row['type'] === SystemLogType::LOGIN) {
                $text = str_replace('###IP###', $row['IP'], $text);
            }
            $emailData[] = [
                'row' => $row,
                'text' => $text,
            ];
        }
        $email = GeneralUtility::makeInstance(FluidEmail::class)
            ->to($this->notificationRecipientEmailAddress)
            ->setTemplate('Security/LoginAttemptFailedWarning')
            ->assign('lines', $emailData);
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $email->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        try {
            GeneralUtility::makeInstance(Mailer::class)->send($email);
        } catch (TransportExceptionInterface $e) {
            // Sending mail failed. Probably broken smtp setup.
            // @todo: Maybe log that sending mail failed.
        }
    }

    /**
     * @param int $earliestLogDate
     * @param int $loginAction
     * @return QueryBuilder
     */
    protected function createPreparedQueryBuilder(int $earliestLogDate, int $loginAction): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_log');
        $queryBuilder
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq(
                    'type',
                    $queryBuilder->createNamedParameter(SystemLogType::LOGIN, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'action',
                    $queryBuilder->createNamedParameter($loginAction, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'tstamp',
                    $queryBuilder->createNamedParameter($earliestLogDate, \PDO::PARAM_INT)
                )
            );
        return $queryBuilder;
    }
}
