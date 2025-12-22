/**
 * GameRecord model - работа с сохраненными играми
 * Аналог GameRecord.php из PHP версии
 */
import { Database } from './Database.js';

export class GameRecord {
    /**
     * Get all saved games
     */
    static async getAllGames() {
        try {
            const games = await Database.getAllGames();
            return games.map(game => ({
                id: game.id,
                playerName: game.playerName,
                secretWord: game.secretWord,
                isWon: game.isWon,
                playedAt: game.playedAt,
                attemptsCount: game.attemptsCount || 0
            }));
        } catch (error) {
            console.error('Error getting games:', error);
            return [];
        }
    }

    /**
     * Get game by ID
     */
    static async getGameById(gameId) {
        try {
            return await Database.getGameById(gameId);
        } catch (error) {
            console.error('Error getting game:', error);
            return null;
        }
    }

    /**
     * Get game statistics
     */
    static async getStatistics() {
        try {
            return await Database.getStatistics();
        } catch (error) {
            console.error('Error getting statistics:', error);
            return {
                total: 0,
                won: 0,
                lost: 0,
                winRate: 0
            };
        }
    }

    /**
     * Format game date for display
     */
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Format game result for display
     */
    static formatGameResult(game) {
        const result = game.isWon ? 'Победа' : 'Поражение';
        const playedAt = this.formatDate(game.playedAt);
        
        return `Игра #${game.id} | Игрок: ${game.playerName} | Слово: ${game.secretWord.toUpperCase()} | Результат: ${result} | Попыток: ${game.attemptsCount} | Дата: ${playedAt}`;
    }

    /**
     * Format attempt for display
     */
    static formatAttempt(attempt) {
        const result = attempt.isCorrect ? '✓ правильно' : '✗ неверно';
        const letter = attempt.letter.length > 1 
            ? `слово "${attempt.letter.toUpperCase()}"` 
            : `буква "${attempt.letter.toUpperCase()}"`;
        
        return `Попытка ${attempt.attemptNumber}: ${letter} - ${result}`;
    }

    /**
     * Clear all games
     */
    static async clearGames() {
        try {
            await Database.clearGames();
            return true;
        } catch (error) {
            console.error('Error clearing games:', error);
            return false;
        }
    }

    /**
     * Simulate game replay
     */
    static async replayGame(gameId) {
        const game = await this.getGameById(gameId);
        
        if (!game) {
            return null;
        }

        const replaySteps = [];
        let currentWord = '';
        let mistakes = 0;

        // Simulate each attempt
        for (const attempt of game.attempts) {
            const step = {
                attemptNumber: attempt.attemptNumber,
                letter: attempt.letter,
                isCorrect: attempt.isCorrect,
                beforeWord: currentWord || '_ '.repeat(game.secretWord.length).trim(),
                afterWord: '',
                mistakes: mistakes
            };

            // Update word state
            const wordLetters = game.secretWord.split('');
            const guessedLetters = game.attempts
                .filter(a => a.attemptNumber <= attempt.attemptNumber && a.isCorrect)
                .map(a => a.letter.length === 1 ? a.letter : null)
                .filter(Boolean);

            currentWord = wordLetters.map(letter => 
                guessedLetters.includes(letter) ? letter.toUpperCase() + ' ' : '_ '
            ).join('').trim();

            step.afterWord = currentWord;

            // Update mistakes
            if (!attempt.isCorrect && attempt.letter.length === 1) {
                mistakes++;
            }

            step.mistakes = mistakes;
            replaySteps.push(step);
        }

        return {
            gameInfo: game,
            replaySteps: replaySteps
        };
    }
}
