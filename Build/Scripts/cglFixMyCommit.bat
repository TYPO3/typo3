@ECHO OFF
FOR /F %%i in ('git diff-tree --no-commit-id --name-only -r HEAD ^| findstr -i ".*\.php"') DO (
	bin\php-cs-fixer.bat fix --path-mode intersection --config=Build/.php_cs %%i
)
