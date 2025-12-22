<?php

namespace DmitriyKorotkevich\Hangman;

use function cli\prompt;

class Controller
{
    public static function run(array $argv): void
    {
        $options = getopt("nlr:h", ["new", "list", "replay:", "help"]);

        if (isset($options['h']) || isset($options['help'])) {
            View::showHelp();
            return;
        }

        if (isset($options['l']) || isset($options['list'])) {
            View::showMessage("Режим LIST: база данных пока не подключена. Список игр недоступен.");
            return;
        }

        if (isset($options['r']) || isset($options['replay'])) {
            $id = $options['r'] ?? $options['replay'];
            View::showMessage("Режим REPLAY: база данных пока не подключена. Повтор игры #$id невозможен.");
            return;
        }

        self::startGame();
    }

    public static function startGame(): void
    {
        View::showWelcome();
        $words = ["planet", "bridge", "guitar", "window", "forest", "school"];
        $word = $words[array_rand($words)];

        $guessed = [];
        $mistakes = 0;
        $maxMistakes = 6;

        while ($mistakes < $maxMistakes) {
            View::showWord($word, $guessed);
            View::showHangman($mistakes);

            $letter = strtolower(prompt("Введите букву"));

            if (strlen($letter) !== 1 || !ctype_alpha($letter)) {
                echo "Введите одну букву!\n";
                continue;
            }

            if (in_array($letter, $guessed)) {
                echo "Эта буква уже вводилась!\n";
                continue;
            }

            $guessed[] = $letter;

            if (!str_contains($word, $letter)) {
                $mistakes++;
            }

            $allGuessed = true;
            foreach (str_split($word) as $ch) {
                if (!in_array($ch, $guessed)) {
                    $allGuessed = false;
                    break;
                }
            }

            if ($allGuessed) {
                View::showWord($word, $guessed);
                View::gameOver(true, $word);
                return;
            }
        }

        View::showHangman($mistakes);
        View::gameOver(false, $word);
    }
}