<?php

namespace DmitriyKorotkevich\Hangman;

class View
{
    private static array $hangmanStages = [
        "+---+\n    |\n    |\n    |\n   ===",
        "+---+\n    |\n    0   |\n    |\n    |\n   ===",
        "+---+\n    |\n    0   |\n    |   |\n    |\n   ===",
        "+---+\n    |\n    0   |\n   /|   |\n    |\n   ===",
        "+---+\n    |\n    0   |\n   /|\\  |\n    |\n   ===",
        "+---+\n    |\n    0   |\n   /|\\  |\n   /    |\n   ===",
        "+---+\n    |\n    0   |\n   /|\\  |\n   / \\  |\n   ===",
    ];

    public static function showWelcome(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "        ИГРА 'ВИСЕЛИЦА' (RedBeanPHP ORM)\n";
        echo str_repeat('=', 50) . "\n\n";
    }

    public static function showGameState(
        string $currentWord,
        array $wrongLetters,
        int $attemptsLeft,
        int $mistakes
    ): void {
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "Слово: " . $currentWord . "\n";
        
        if (!empty($wrongLetters)) {
            echo "Ошибочные буквы: " . implode(', ', array_map('strtoupper', $wrongLetters)) . "\n";
        }
        
        echo "Осталось попыток: $attemptsLeft\n";
        echo "Ошибок: $mistakes из 6\n\n";
        
        self::showHangman($mistakes);
    }

    public static function showHangman(int $mistakes): void
    {
        echo self::$hangmanStages[min($mistakes, 6)] . "\n";
    }

    public static function showGameOver(bool $won, string $word, int $mistakes): void
    {
        echo "\n" . str_repeat('*', 50) . "\n";
        
        if ($won) {
            echo "              🎉 ПОБЕДА! 🎉\n";
            echo "Вы угадали слово: " . strtoupper($word) . "\n";
        } else {
            echo "              💀 ПРОИГРЫШ 💀\n";
            echo "Загаданное слово: " . strtoupper($word) . "\n";
        }
        
        echo "Количество ошибок: $mistakes\n";
        echo str_repeat('*', 50) . "\n\n";
        
        echo "Игра сохранена через RedBeanPHP ORM\n";
    }

    public static function showHelp(): void
    {
        echo "\nИГРА 'ВИСЕЛИЦА' - Версия с RedBeanPHP ORM\n\n";
        echo "Использование: hangman [ПАРАМЕТРЫ]\n\n";
        echo "Параметры:\n";
        echo "  -n, --new          Новая игра (режим по умолчанию)\n";
        echo "  -l, --list         Показать список всех сохраненных игр\n";
        echo "  -r, --replay ID    Повторить игру с указанным ID\n";
        echo "  -s, --stats        Показать статистику игр\n";
        echo "  -h, --help         Показать эту справку\n\n";
        echo "Примеры:\n";
        echo "  hangman                    # Новая игра\n";
        echo "  hangman --list             # Список всех игр\n";
        echo "  hangman --replay 5         # Повторить игру #5\n";
        echo "  hangman --stats            # Статистика\n";
        echo "  hangman --help             # Справка\n\n";
        echo "База данных: SQLite с RedBeanPHP ORM\n";
    }

    public static function showMessage(string $text): void
    {
        echo $text . "\n";
    }

    public static function showError(string $text): void
    {
        echo "❌ Ошибка: " . $text . "\n";
    }

    public static function showGameListHeader(): void
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "              СПИСОК СОХРАНЕННЫХ ИГР (RedBeanPHP)\n";
        echo str_repeat('=', 70) . "\n\n";
    }

    public static function showGameListItem(array $game): void
    {
        $result = $game['is_won'] ? 'ПОБЕДА' : 'ПОРАЖЕНИЕ';
        $date = date('d.m.Y H:i', strtotime($game['played_at']));
        
        printf(
            "Игра #%-4d | Игрок: %-15s | Слово: %-8s | %-12s | Попыток: %-3d | %s\n",
            $game['id'],
            $game['player_name'],
            strtoupper($game['secret_word']),
            $result,
            $game['attempts_count'],
            $date
        );
    }

    public static function showStatisticsHeader(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "           СТАТИСТИКА ИГР (RedBeanPHP ORM)\n";
        echo str_repeat('=', 50) . "\n\n";
    }

    public static function showStatistics(array $stats): void
    {
        echo "Всего сыграно игр: " . $stats['total'] . "\n";
        echo "Побед: " . $stats['won'] . "\n";
        echo "Поражений: " . $stats['lost'] . "\n";
        echo "Процент побед: " . $stats['win_rate'] . "%\n\n";
        
        if ($stats['total'] > 0) {
            $barLength = 30;
            $wonBar = round($stats['won'] / $stats['total'] * $barLength);
            $lostBar = $barLength - $wonBar;
            
            echo "Визуализация:\n";
            echo "Победы:   [" . str_repeat('█', $wonBar) . str_repeat(' ', $lostBar) . "]\n";
            echo "Поражения:[" . str_repeat('█', $lostBar) . str_repeat(' ', $wonBar) . "]\n";
        }
    }

    public static function showReplayHeader(array $gameData): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "        ПОВТОРЕНИЕ ИГРЫ #" . $gameData['id'] . " (RedBeanPHP)\n";
        echo str_repeat('=', 60) . "\n\n";
        
        echo "Игрок: " . $gameData['player_name'] . "\n";
        echo "Слово: " . strtoupper($gameData['secret_word']) . "\n";
        echo "Дата: " . date('d.m.Y H:i', strtotime($gameData['played_at'])) . "\n";
        echo "Результат: " . ($gameData['is_won'] ? 'Победа' : 'Поражение') . "\n\n";
        
        echo "Ход игры:\n";
        echo str_repeat('-', 60) . "\n";
    }

    public static function showReplayAttempt(int $number, string $letter, bool $isCorrect): void
    {
        $status = $isCorrect ? '✓ правильно' : '✗ неверно';
        $formattedLetter = strlen($letter) > 1 ? "слово '" . strtoupper($letter) . "'" : "буква '" . strtoupper($letter) . "'";
        
        printf("Попытка %2d: %-25s -> %s\n", $number, $formattedLetter, $status);
    }

    public static function showWordState(string $word): void
    {
        echo "Текущее слово: " . $word . "\n";
    }

    public static function showReplayResult(array $gameData): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Игра завершена. Исход: " . ($gameData['is_won'] ? 'ПОБЕДА' : 'ПОРАЖЕНИЕ') . "\n";
        echo "Всего попыток: " . count($gameData['attempts']) . "\n";
        echo "Данные получены через RedBeanPHP ORM\n";
        echo str_repeat('=', 60) . "\n\n";
    }
}
