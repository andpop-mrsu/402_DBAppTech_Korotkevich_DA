<?php
/**
 * Контроллер игры "Виселица"
 */

namespace Dmitriykorotkevich\Hangman;

class Controller
{
    /**
     * Запуск игры
     */
    public static function startGame(): void
    {
        // Инициализируем View
        $view = new View();
        
        // Выводим начальный экран
        $view->showWelcomeScreen();
        
        echo "Игра 'Виселица' запущена!\n";
        
        // Простой ввод
        echo "Как вас зовут? ";
        $name = trim(fgets(STDIN));
        echo "Привет, $name! Давайте начнем игру.\n";
        
        // Показываем игровой экран
        $view->showGameScreen();
        
        // Запускаем основную логику
        self::playGame();
    }
    
    /**
     * Основная игровая логика
     */
    public static function playGame(): void
    {
        echo "\n=== ТЕСТОВЫЙ РЕЖИМ ===\n";
        echo "Игровая логика будет полностью реализована\n";
        echo "в следующих лабораторных работах.\n\n";
        
        echo "Хотите выйти? (y/n) ";
        $choice = strtolower(trim(fgets(STDIN)));
        
        if ($choice === 'y') {
            echo "До свидания!\n";
        } else {
            echo "Продолжаем тестирование...\n";
        }
    }
}
