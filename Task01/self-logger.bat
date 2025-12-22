@echo off
chcp 1251 > nul
echo.

:: Проверяем наличие sqlite3
where sqlite3 >nul 2>nul
if errorlevel 1 (
    echo ОШИБКА: SQLite3 не найден в PATH
    echo Скачайте с https://www.sqlite.org/download.html
    pause
    exit /b 1
)

set DB_FILE=launch_stats.db
set USERNAME=%USERNAME%

echo Создание базы данных...
sqlite3 "%DB_FILE%" "CREATE TABLE IF NOT EXISTS launches (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, launch_time TEXT);"

echo Добавление записи о запуске...
:: Получаем дату в формате YYYY.MM.DD HH:MM
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set DT=%%I
set LAUNCH_TIME=%DT:~0,4%.%DT:~4,2%.%DT:~6,2% %DT:~8,2%:%DT:~10,2%

sqlite3 "%DB_FILE%" "INSERT INTO launches (username, launch_time) VALUES ('%USERNAME%', '%LAUNCH_TIME%');"

echo.
echo ============================================
echo Имя программы: self-logger.bat

for /f %%a in ('sqlite3 "%DB_FILE%" "SELECT COUNT(*) FROM launches;"') do set TOTAL=%%a
echo Количество запусков: %TOTAL%

for /f %%a in ('sqlite3 "%DB_FILE%" "SELECT launch_time FROM launches ORDER BY launch_time LIMIT 1;"') do set FIRST=%%a
echo Первый запуск: %FIRST%

echo ============================================
echo User      ^| Date
echo ============================================

sqlite3 -header -column "%DB_FILE%" "SELECT username, launch_time FROM launches ORDER BY launch_time DESC;"

echo ============================================
pause