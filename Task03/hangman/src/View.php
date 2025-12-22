<?php

namespace DmitriyKorotkevich\Hangman;

class View
{
    public static function showWelcome(): void
    {
        echo "=============================\n";
        echo " Добро пожаловать в 'Виселицу'!\n";
        echo "=============================\n\n";
    }

    public static function showWord(string $word, array $guessed): void
    {
        $output = '';
        foreach (str_split($word) as $letter) {
            $output .= in_array($letter, $guessed) ? $letter . ' ' : '_ ';
        }
        echo $output . "\n";
    }

    public static function showHangman(int $mistakes): void
    {
        $stages = [
            "",
            " O ",
            " O \n | ",
            " O \n/| ",
            " O \n/|\\",
            " O \n/|\\\n/ ",
            " O \n/|\\\n/ \\",
        ];
        echo $stages[$mistakes] . "\n";
    }

    public static function gameOver(bool $won, string $word): void
    {
        if ($won) {
            echo "\nПоздравляем! Ты выиграл!\n";
        } else {
            echo "\nТы проиграл :( Загаданное слово: $word\n";
        }
    }

    public static function showHelp(): void
    {
        echo "Использование: hangman [options]\n";
        echo "  -n, --new      Новая игра (по умолчанию)\n";
        echo "  -l, --list     Список игр (пока недоступно)\n";
        echo "  -r, --replay   Повтор игры (пока недоступно)\n";
        echo "  -h, --help     Показать справку\n";
    }

    public static function showMessage(string $text): void
    {
        echo $text . "\n";
    }
}