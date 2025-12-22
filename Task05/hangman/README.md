# Игра "Виселица" (Hangman) - Версия с RedBeanPHP ORM

Консольная реализация классической игры "Виселица" на PHP с использованием RedBeanPHP ORM для работы с базой данных.

## Требования
- PHP 7.4 или выше
- Расширения: PDO, PDO_SQLite
- Composer для управления зависимостями

## Установка

### Установка из репозитория:
```bash
git clone https://github.com/DmitriyKorotkevich/402_DBAppTech_Korotkevich_DA.git
cd 402_DBAppTech_Korotkevich_DA/Task05/hangman
composer install
chmod +x bin/hangman
Установка через Packagist (глобально):
bash
composer global require dmitriykorotkevich/hangman
После глобальной установки добавьте в PATH:

bash
# Windows (Git Bash):
export PATH="$PATH:/c/Users/$USER/AppData/Roaming/Composer/vendor/bin"
Использование
Параметры командной строки:
text
hangman [ПАРАМЕТРЫ]

Параметры:
  -n, --new          Новая игра (режим по умолчанию)
  -l, --list         Показать список всех сохраненных игр
  -r, --replay ID    Повторить игру с указанным ID
  -s, --stats        Показать статистику игр
  -h, --help         Показать справку
Примеры:
bash
# Новая игра (запросит имя игрока)
hangman

# Показать список всех сохраненных игр
hangman --list
hangman -l

# Повторить игру #5
hangman --replay 5
hangman -r 5

# Показать статистику
hangman --stats
hangman -s

# Справка
hangman --help
hangman -h
Особенности реализации с RedBeanPHP
Преимущества ORM:
Упрощенный код - нет SQL запросов в коде

Автоматическое создание таблиц - RedBeanPHP создает таблицы при необходимости

Объектно-ориентированный подход - работа с данными как с объектами

Безопасность - автоматическое экранирование данных

Структура базы данных через RedBeanPHP:
RedBeanPHP автоматически создает следующие таблицы:

game - информация об играх

attempt - попытки в играх

word - словарь слов

Пример кода с RedBeanPHP:
php
// Сохранение игры (старый способ с SQL)
// $stmt = $pdo->prepare("INSERT INTO games (...) VALUES (...)");

// Новый способ с RedBeanPHP
$game = R::dispense('game');
$game->playerName = $playerName;
$game->secretWord = $word;
$game->isWon = $isWon;
$id = R::store($game);
Архитектура проекта
Проект использует RedBeanPHP ORM для работы с базой данных:

Модели (Models/):
Database.php - инициализация RedBeanPHP

Game.php - игровая логика

GameRecord.php - работа с сохраненными играми через ORM

Основные изменения по сравнению с Lab 4:
Убраны SQL-запросы - вместо них используются методы RedBeanPHP

Автоматическое создание таблиц - RedBeanPHP создает таблицы при первом использовании

Упрощенный код сохранения - использует объекты вместо массивов параметров

Пример сравнения кода:
Lab 4 (чистый SQL):

php
$stmt = $db->prepare("INSERT INTO games (...) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $word, $isWon, $date]);
Lab 5 (RedBeanPHP ORM):

php
$game = R::dispense('game');
$game->playerName = $name;
$game->secretWord = $word;
$game->isWon = $isWon;
$game->playedAt = $date;
R::store($game);
Новый функционал
Статистика игр:
Команда hangman --stats показывает:

Общее количество игр

Количество побед и поражений

Процент побед

Визуализацию в виде графиков

Автоматическая миграция:
При первом запуске RedBeanPHP автоматически:

Создает файл базы данных если его нет

Создает необходимые таблицы

Заполняет таблицу слов начальными значениями

Соответствие стандартам
Код соответствует стандартам PSR-1 и PSR-12. Для проверки:

bash
composer cs-check    # Проверить код
composer cs-fix      # Автоматически исправить ошибки
Публикация на Packagist
Пакет обновлен на Packagist: https://packagist.org/packages/dmitriykorotkevich/hangman

Версия 1.4.0 включает:

Полный переход на RedBeanPHP ORM

Удаление всех SQL-запросов из кода

Новый функционал: статистика игр

Автоматическое создание таблиц

Соответствие стандартам PSR-1, PSR-12

Автор
Дмитрий Короткевич
GitHub: DmitriyKorotkevich
Email: githubdmitriy@gmail.com