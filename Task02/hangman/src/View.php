<?php
/**
 * Представление (View) для игры "Виселица"
 */

namespace Dmitriykorotkevich\Hangman;

class View
{
    /**
     * Показывает приветственный экран
     */
    public function showWelcomeScreen(): void
    {
        echo "========================================\n";
        echo "         ИГРА 'ВИСЕЛИЦА'\n";
        echo "========================================\n\n";
        echo "Правила:\n";
        echo "1. Компьютер загадывает слово из 6 букв\n";
        echo "2. Вы угадываете буквы по одной\n";
        echo "3. За каждую ошибку рисуется часть виселицы\n";
        echo "4. Цель: угадать слово до завершения рисунка\n\n";
    }
    
    /**
     * Показывает игровой экран
     */
    public function showGameScreen(): void
    {
        echo "\n------------- ИГРОВОЕ ПОЛЕ -------------\n";
        echo "Слово: _ _ _ _ _ _\n";
        echo "Ошибки: 0/6\n";
        echo "Использованные буквы:\n\n";
    }
    
    /**
     * Отображает виселицу
     * @param int $mistakes Количество ошибок (0-6)
     */
    public function showGallows(int $mistakes): void
    {
        $stages = [
            '',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    ' . PHP_EOL . '  |   ' . PHP_EOL . '  |    ' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    O' . PHP_EOL . '  |   ' . PHP_EOL . '  |    ' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    O' . PHP_EOL . '  |    |' . PHP_EOL . '  |    ' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    O' . PHP_EOL . '  |   /|' . PHP_EOL . '  |    ' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    O' . PHP_EOL . '  |   /|\\' . PHP_EOL . '  |    ' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    O' . PHP_EOL . '  |   /|\\' . PHP_EOL . '  |   /' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
            '   ____' . PHP_EOL . '  |    |' . PHP_EOL . '  |    O' . PHP_EOL . '  |   /|\\' . PHP_EOL . '  |   / \\' . PHP_EOL . '  |    ' . PHP_EOL . '__|__',
        ];
        
        echo $stages[$mistakes] ?? $stages[0] . "\n";
    }
}
