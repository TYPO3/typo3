#
# Add default backend user
#
INSERT INTO be_users (pid, username, password, admin, usergroup, disable, starttime, endtime, lang, email, db_mountpoints, options, realName, userMods, allowed_languages, uc, file_mountpoints, fileoper_perms, workspace_perms, lockToDomain, disableIPlock, deleted, TSconfig, lastlogin, createdByAction, usergroup_cached_list, workspace_id, workspace_preview) VALUES (0, '_frontend', '$1$0qRi98gj$9der3HCejkhn8RhuMZ4YN/', 0, '', 0, 0, 0, '', '', '', 3, 'Backend user in frontend context', '', '', NULL, '', 0, 1, '', 0, 0, '', 0, 0, NULL, 0, 1);
