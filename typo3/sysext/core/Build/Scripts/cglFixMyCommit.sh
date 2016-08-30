#!/bin/bash

#########################
#
# CGL check latest core commit.
#
# It expects to be run from the core root.
#
# To auto-fix single files, use the php-cs-fixer command directly
# substitute $FILE with a filename
#
##########################

COUNTER=0

for FILE in $(git diff-tree --no-commit-id --name-only -r HEAD | grep '.php$'); do
    if [ -e $FILE ]
    then
        # If changes to the fixers are done, please sync them with Build/.php_cs from core root directory
        ./bin/php-cs-fixer fix $FILE \
            -v \
            --level=psr2 \
            --fixers=remove_leading_slash_use,single_array_no_trailing_comma,spaces_before_semicolon,unused_use,concat_with_spaces,whitespacy_lines,ordered_use,single_quote,duplicate_semicolon,extra_empty_lines,phpdoc_no_package,phpdoc_scalar,no_empty_lines_after_phpdocs,short_array_syntax,array_element_white_space_after_comma,function_typehint_space,hash_to_slash_comment,join_function,lowercase_cast,namespace_no_leading_whitespace,native_function_casing,no_empty_statement,self_accessor,short_bool_cast,unneeded_control_parentheses
        if [ "$?" = "1" ]
        then
            COUNTER=$((COUNTER+1))
        fi
    fi
done

if [ ${COUNTER} -gt 0 ] ; then
    echo "$COUNTER number of files are not CGL clean. Check $0 to find out what is going wrong."
    exit 1
fi

exit 0
