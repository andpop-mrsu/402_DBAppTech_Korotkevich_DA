/**
 * Game model - логика игры "Виселица"
 * Аналог Game.php из PHP версии
 */
import { Database } from './Database.js';

export class Game {
    constructor(playerName = 'Игрок') {
        this.playerName = playerName;
        this.word = '';
        this.guessedLetters = [];
        this.wrongLetters = [];
        this.maxAttempts = 6;
        this.attemptsLeft = this.maxAttempts;
        this.mistakes = 0;
        this.isFinished = false;
        this.isWon = false;
        this.attemptsHistory = [];
        this.gameId = null;
        this.startedAt = new Date();
    }

    /**
     * Initialize new game
     */
    async initialize() {
        try {
            this.word = await Database.getRandomWord();
            this.attemptsLeft = this.maxAttempts;
            this.mistakes = 0;
            this.guessedLetters = [];
            this.wrongLetters = [];
            this.isFinished = false;
            this.isWon = false;
            this.attemptsHistory = [];
            this.gameId = null;
            this.startedAt = new Date();
            
            return true;
        } catch (error) {
            console.error('Failed to initialize game:', error);
            return false;
        }
    }

    /**
     * Process guess - letter or word
     */
    guess(input) {
        if (this.isFinished) {
            return { success: false, message: 'Игра уже завершена' };
        }

        input = input.toLowerCase().trim();

        if (input.length === 0) {
            return { success: false, message: 'Введите букву или слово' };
        }

        if (input.length > 1) {
            return this.guessWord(input);
        }

        return this.guessLetter(input);
    }

    /**
     * Guess a single letter
     */
    guessLetter(letter) {
        // Check if already guessed
        if (this.guessedLetters.includes(letter) || this.wrongLetters.includes(letter)) {
            return { 
                success: false, 
                message: `Буква "${letter.toUpperCase()}" уже была использована` 
            };
        }

        const isCorrect = this.word.includes(letter);

        // Record attempt
        this.attemptsHistory.push({
            letter: letter,
            isCorrect: isCorrect,
            timestamp: new Date().toISOString()
        });

        if (isCorrect) {
            this.guessedLetters.push(letter);
            
            // Check if word is completely guessed
            if (this.isWordGuessed()) {
                this.finishGame(true);
                return { 
                    success: true, 
                    letter: letter, 
                    position: 'correct',
                    gameFinished: true,
                    won: true
                };
            }
            
            return { 
                success: true, 
                letter: letter, 
                position: 'correct',
                gameFinished: false
            };
        } else {
            this.wrongLetters.push(letter);
            this.mistakes++;
            this.attemptsLeft--;

            if (this.attemptsLeft <= 0) {
                this.finishGame(false);
                return { 
                    success: true, 
                    letter: letter, 
                    position: 'wrong',
                    gameFinished: true,
                    won: false
                };
            }

            return { 
                success: true, 
                letter: letter, 
                position: 'wrong',
                gameFinished: false
            };
        }
    }

    /**
     * Guess the whole word
     */
    guessWord(word) {
        const isCorrect = word === this.word;

        // Record attempt
        this.attemptsHistory.push({
            letter: word,
            isCorrect: isCorrect,
            timestamp: new Date().toISOString()
        });

        if (isCorrect) {
            this.guessedLetters = [...new Set(this.word.split(''))];
            this.finishGame(true);
            return { 
                success: true, 
                message: 'Вы угадали слово!',
                gameFinished: true,
                won: true
            };
        } else {
            this.attemptsLeft = 0;
            this.finishGame(false);
            this.mistakes = this.maxAttempts;
            return { 
                success: false, 
                message: 'Неправильное слово',
                gameFinished: true,
                won: false
            };
        }
    }

    /**
     * Finish game and save to database
     */
    async finishGame(isWon) {
        this.isFinished = true;
        this.isWon = isWon;

        // Save game to database
        try {
            this.gameId = await Database.saveGame({
                playerName: this.playerName,
                secretWord: this.word,
                isWon: isWon,
                playedAt: this.startedAt.toISOString(),
                attempts: this.attemptsHistory.map(attempt => ({
                    letter: attempt.letter,
                    isCorrect: attempt.isCorrect
                }))
            });
        } catch (error) {
            console.error('Failed to save game:', error);
        }
    }

    /**
     * Check if word is completely guessed
     */
    isWordGuessed() {
        for (let i = 0; i < this.word.length; i++) {
            if (!this.guessedLetters.includes(this.word[i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get current word state with blanks
     */
    getCurrentWord() {
        let result = '';
        for (let i = 0; i < this.word.length; i++) {
            result += this.guessedLetters.includes(this.word[i]) 
                ? this.word[i].toUpperCase() + ' ' 
                : '_ ';
        }
        return result.trim();
    }

    /**
     * Get word as array for display
     */
    getWordArray() {
        return this.word.split('').map(letter => ({
            letter: letter.toUpperCase(),
            guessed: this.guessedLetters.includes(letter)
        }));
    }

    /**
     * Get hint (first letter)
     */
    getHint() {
        return this.word[0].toUpperCase();
    }

    /**
     * Get ASCII hangman based on mistakes
     */
    getHangman() {
        const stages = [
            // 0 mistakes
            `+---+
    |
    |
    |
   ===`,
            // 1 mistake
            `+---+
    |
    0   |
    |
    |
   ===`,
            // 2 mistakes
            `+---+
    |
    0   |
    |   |
    |
   ===`,
            // 3 mistakes
            `+---+
    |
    0   |
   /|   |
    |
   ===`,
            // 4 mistakes
            `+---+
    |
    0   |
   /|\\  |
    |
   ===`,
            // 5 mistakes
            `+---+
    |
    0   |
   /|\\  |
   /    |
   ===`,
            // 6 mistakes (game over)
            `+---+
    |
    0   |
   /|\\  |
   / \\  |
   ===`
        ];

        return stages[Math.min(this.mistakes, stages.length - 1)];
    }

    /**
     * Reset game
     */
    reset() {
        this.guessedLetters = [];
        this.wrongLetters = [];
        this.attemptsLeft = this.maxAttempts;
        this.mistakes = 0;
        this.isFinished = false;
        this.isWon = false;
        this.attemptsHistory = [];
    }

    // Getters
    getWord() {
        return this.word.toUpperCase();
    }

    getPlayerName() {
        return this.playerName;
    }

    getGuessedLetters() {
        return [...this.guessedLetters];
    }

    getWrongLetters() {
        return [...this.wrongLetters];
    }

    getAttemptsLeft() {
        return this.attemptsLeft;
    }

    getMaxAttempts() {
        return this.maxAttempts;
    }

    getMistakes() {
        return this.mistakes;
    }

    getIsFinished() {
        return this.isFinished;
    }

    getIsWon() {
        return this.isWon;
    }

    getAttemptsHistory() {
        return [...this.attemptsHistory];
    }

    getGameId() {
        return this.gameId;
    }

    getStartedAt() {
        return this.startedAt;
    }
}
