/**
 * GameController - управление игровым процессом
 * Аналог Controller.php из PHP версии
 */
import { Game } from '../models/Game.js';
import { GameRecord } from '../models/GameRecord.js';

export class GameController {
    constructor(view) {
        this.view = view;
        this.game = null;
        this.currentMode = 'game';
    }

    /**
     * Initialize controller
     */
    async initialize() {
        await this.loadStatistics();
        await this.loadGamesHistory();
        this.view.initializeKeyboard(this.handleLetterClick.bind(this));
        this.view.initializeModeButtons(this.handleModeChange.bind(this));
        this.view.initializeControls(this);
        
        // Load default player name from localStorage
        const savedName = localStorage.getItem('hangman_playerName');
        if (savedName) {
            document.getElementById('playerName').value = savedName;
        }
    }

    /**
     * Start new game
     */
    async startNewGame() {
        const playerName = document.getElementById('playerName').value.trim() || 'Игрок';
        
        // Save player name to localStorage
        localStorage.setItem('hangman_playerName', playerName);
        
        this.game = new Game(playerName);
        const success = await this.game.initialize();
        
        if (success) {
            this.view.showGameStarted(this.game);
            this.view.showMessage(`Новая игра начата! Игрок: ${playerName}`, 'info');
            this.updateGameInfo();
            
            // Switch to game mode
            this.handleModeChange('game');
        } else {
            this.view.showMessage('Не удалось начать игру. Попробуйте еще раз.', 'error');
        }
    }

    /**
     * Handle letter click from keyboard
     */
    async handleLetterClick(letter) {
        if (!this.game || this.game.getIsFinished()) {
            this.view.showMessage('Начните новую игру!', 'error');
            return;
        }

        const result = this.game.guess(letter);
        
        if (result.success) {
            this.view.updateGameState(this.game);
            
            if (result.gameFinished) {
                if (result.won) {
                    this.view.showMessage(`🎉 Поздравляем! Вы победили! Слово: ${this.game.getWord()}`, 'success');
                } else {
                    this.view.showMessage(`💀 Игра окончена! Загаданное слово: ${this.game.getWord()}`, 'error');
                }
                
                // Refresh history and statistics
                await this.loadGamesHistory();
                await this.loadStatistics();
                
                // Show game saved message
                this.view.showMessage('Игра сохранена в IndexedDB', 'info');
            }
        } else {
            this.view.showMessage(result.message, 'error');
        }
        
        this.updateGameInfo();
    }

    /**
     * Handle word guess
     */
    async handleWordGuess(word) {
        if (!this.game || this.game.getIsFinished()) {
            this.view.showMessage('Начните новую игру!', 'error');
            return;
        }

        const result = this.game.guessWord(word);
        
        if (result.success) {
            this.view.updateGameState(this.game);
            this.view.showMessage(result.message, 'success');
        } else {
            this.view.showMessage(result.message, 'error');
        }
        
        if (result.gameFinished) {
            if (result.won) {
                this.view.showMessage(`🎉 Поздравляем! Вы победили!`, 'success');
            } else {
                this.view.showMessage(`💀 Игра окончена! Загаданное слово: ${this.game.getWord()}`, 'error');
            }
            
            // Refresh history and statistics
            await this.loadGamesHistory();
            await this.loadStatistics();
        }
        
        this.updateGameInfo();
    }

    /**
     * Show hint
     */
    showHint() {
        if (!this.game || this.game.getIsFinished()) {
            this.view.showMessage('Начните новую игру!', 'error');
            return;
        }

        const hint = this.game.getHint();
        this.view.showMessage(`Подсказка: первая буква слова - ${hint}`, 'info');
    }

    /**
     * Reset current game
     */
    resetGame() {
        if (this.game) {
            this.game.reset();
            this.view.updateGameState(this.game);
            this.view.showMessage('Игра сброшена. Можно продолжить угадывать.', 'info');
            this.updateGameInfo();
        }
    }

    /**
     * Load games history
     */
    async loadGamesHistory() {
        try {
            const games = await GameRecord.getAllGames();
            this.view.updateGamesHistory(games);
        } catch (error) {
            console.error('Error loading games history:', error);
            this.view.showMessage('Не удалось загрузить историю игр', 'error');
        }
    }

    /**
     * Load statistics
     */
    async loadStatistics() {
        try {
            const stats = await GameRecord.getStatistics();
            this.view.updateStatistics(stats);
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    /**
     * Clear games history
     */
    async clearHistory() {
        if (confirm('Вы уверены, что хотите удалить всю историю игр? Это действие нельзя отменить.')) {
            const success = await GameRecord.clearGames();
            
            if (success) {
                this.view.showMessage('История игр очищена', 'success');
                await this.loadGamesHistory();
                await this.loadStatistics();
            } else {
                this.view.showMessage('Не удалось очистить историю игр', 'error');
            }
        }
    }

    /**
     * Replay game by ID
     */
    async replayGame(gameId) {
        try {
            const replay = await GameRecord.replayGame(gameId);
            
            if (replay) {
                this.view.showReplayModal(replay);
            } else {
                this.view.showMessage('Игра не найдена', 'error');
            }
        } catch (error) {
            console.error('Error replaying game:', error);
            this.view.showMessage('Не удалось загрузить игру для повторения', 'error');
        }
    }

    /**
     * Handle mode change
     */
    handleModeChange(mode) {
        this.currentMode = mode;
        this.view.switchMode(mode);
        
        if (mode === 'history') {
            this.loadGamesHistory();
        } else if (mode === 'stats') {
            this.loadStatistics();
        }
    }

    /**
     * Update game info in sidebar
     */
    updateGameInfo() {
        if (this.game) {
            this.view.updateGameInfo({
                currentWord: this.game.getWord(),
                guessedCount: this.game.getGuessedLetters().length,
                movesCount: this.game.getAttemptsHistory().length,
                gameStarted: this.game.getStartedAt().toLocaleTimeString('ru-RU')
            });
        }
    }

    /**
     * Get current game state
     */
    getGameState() {
        return this.game ? {
            playerName: this.game.getPlayerName(),
            word: this.game.getWord(),
            isFinished: this.game.getIsFinished(),
            isWon: this.game.getIsWon(),
            mistakes: this.game.getMistakes(),
            attemptsLeft: this.game.getAttemptsLeft()
        } : null;
    }
}
