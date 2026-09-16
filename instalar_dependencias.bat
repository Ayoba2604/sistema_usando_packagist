@echo off
setlocal
cd /d "%~dp0"

echo ================================================
echo  Instalacao do Sistema de Documentos PHP
echo ================================================

set "PHP_CMD="
where php >nul 2>nul
if not errorlevel 1 (
    php -r "exit(PHP_VERSION_ID >= 80100 ? 0 : 1);" >nul 2>nul
    if not errorlevel 1 set "PHP_CMD=php"
)

rem Atalho para instalacoes padrao do Wamp quando o PHP nao esta no PATH.
if not defined PHP_CMD (
    for /f "delims=" %%P in ('dir /b /ad /o-n "C:\wamp64\bin\php\php*" 2^>nul') do (
        if not defined PHP_CMD if exist "C:\wamp64\bin\php\%%P\php.exe" (
            "C:\wamp64\bin\php\%%P\php.exe" -r "exit(PHP_VERSION_ID >= 80100 ? 0 : 1);" >nul 2>nul
            if not errorlevel 1 set "PHP_CMD=C:\wamp64\bin\php\%%P\php.exe"
        )
    )
)

if not defined PHP_CMD (
    echo ERRO: PHP nao foi encontrado no PATH nem em C:\wamp64\bin\php.
    echo Instale o PHP 8.1+ ou adicione-o ao PATH e execute este arquivo novamente.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    if not exist composer.phar (
        echo Composer nao encontrado. Baixando uma copia local...
        "%PHP_CMD%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        if errorlevel 1 goto :composer_error
        "%PHP_CMD%" composer-setup.php --install-dir=. --filename=composer.phar
        if errorlevel 1 goto :composer_error
        del /q composer-setup.php >nul 2>nul
    )
    "%PHP_CMD%" composer.phar install --no-interaction --prefer-dist --optimize-autoloader
) else (
    composer install --no-interaction --prefer-dist --optimize-autoloader
)

if errorlevel 1 (
    echo.
    echo ERRO: nao foi possivel instalar as dependencias.
    pause
    exit /b 1
)

echo.
echo Dependencias instaladas com sucesso.
echo Configure o arquivo .env e abra index.php pelo seu servidor PHP.
pause
exit /b 0

:composer_error
echo.
echo ERRO: falha ao baixar ou instalar o Composer local.
if exist composer-setup.php del /q composer-setup.php >nul 2>nul
pause
exit /b 1
