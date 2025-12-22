/**
 * View - отображение игры и взаимодействие с пользователем
 * Аналог View.php из PHP версии
 */
export class View {
    constructor() {
        this.keyboardCallback = null;
        this.modeCallback = null;
        this.controller = null;
    }

    /**
     * Initialize view
     */
    initialize() {
        this.createKeyboard();
        this.setupEventListeners();
        this.initializeModals();
    }

    /**
     * Initialize keyboard with callback
     */
    initializeKeyboard(callback) {
        this.keyboardCallback = callback;
    }

    /**
     * Initialize mode buttons
     */
    initializeModeButtons(callback) {
        this.modeCallback = callback;
        
        document.querySelectorAll('.mode-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const mode = e.currentTarget.dataset.mode;
                this.switchMode(mode);
                if (this.modeCallback) {
                    this.modeCallback(mode);
                }
            });
        });
    }

    /**
     * Initialize game controls
     */
    initializeControls(controller) {
        this.controller = controller;
        
        // Start game button
        document.getElementById('startGame').addEventListener('click', () => {
            controller.startNewGame();
        });

        // Guess word button
        document.getElementById('guessWordBtn').addEventListener('click', () => {
            this.showWordGuessModal();
        });

        // Hint button
        document.getElementById('hintBtn').addEventListener('click', () => {
            controller.showHint();
        });

        // Reset game button
        document.getElementById('resetGame').addEventListener('click', () => {
            controller.resetGame();
        });

        // Refresh history button
        document.getElementById('refreshHistory').addEventListener('click', () => {
            controller.loadGamesHistory();
        });

        // Clear history button
        document.getElementById('clearHistory').addEventListener('click', () => {
            controller.clearHistory();
        });
    }

    /**
     * Create virtual keyboard
     */
    createKeyboard() {
        const keyboard = document.getElementById('keyboard');
        keyboard.innerHTML = '';
        
        const russianLetters = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
        
        for (let letter of russianLetters) {
            const key = document.createElement('div');
            key.className = 'key';
            key.textContent = letter.toUpperCase();
            key.dataset.letter = letter;
            
            key.addEventListener('click', () => {
                if (this.keyboardCallback) {
                    this.keyboardCallback(letter);
                    this.markKeyUsed(key, 'pending');
                }
            });
            
            keyboard.appendChild(key);
        }
    }

    /**
     * Show game started
     */
    showGameStarted(game) {
        // Update player info
        document.getElementById('currentPlayer').textContent = `Игрок: ${game.getPlayerName()}`;
        
        // Update word display
        this.updateWordDisplay(game);
        
        // Update hangman
        document.getElementById('hangmanDisplay').textContent = game.getHangman();
        
        // Update counters
        document.getElementById('mistakesCount').textContent = game.getMistakes();
        document.getElementById('attemptsLeft').textContent = game.getAttemptsLeft();
        document.getElementById('wordLength').textContent = game.getWord().length;
        
        // Clear wrong letters
        document.getElementById('wrongLetters').textContent = '';
        
        // Reset keyboard
        document.querySelectorAll('.key').forEach(key => {
            key.className = 'key';
        });
        
        // Show game status
        this.showMessage('Новая игра начата! Угадайте слово.', 'info');
    }

    /**
     * Update game state
     */
    updateGameState(game) {
        // Update word display
        this.updateWordDisplay(game);
        
        // Update hangman
        document.getElementById('hangmanDisplay').textContent = game.getHangman();
        
        // Update counters
        document.getElementById('mistakesCount').textContent = game.getMistakes();
        document.getElementById('attemptsLeft').textContent = game.getAttemptsLeft();
        
        // Update wrong letters
        const wrongLetters = game.getWrongLetters().map(l => l.toUpperCase()).join(', ');
        document.getElementById('wrongLetters').textContent = wrongLetters || 'нет';
        
        // Update keyboard
        this.updateKeyboard(game);
    }

    /**
     * Update word display
     */
    updateWordDisplay(game) {
        const wordDisplay = document.getElementById('wordDisplay');
        wordDisplay.innerHTML = '';
        
        const wordArray = game.getWordArray();
        
        wordArray.forEach((letterObj, index) => {
            const letterBox = document.createElement('div');
            letterBox.className = letterObj.guessed ? 'letter-box' : 'letter-box empty';
            
            if (letterObj.guessed) {
                letterBox.textContent = letterObj.letter;
                letterBox.classList.add('animate-fade');
            }
            
            wordDisplay.appendChild(letterBox);
        });
    }

    /**
     * Update keyboard state
     */
    updateKeyboard(game) {
        document.querySelectorAll('.key').forEach(key => {
            const letter = key.dataset.letter;
            
            if (game.getGuessedLetters().includes(letter)) {
                key.className = 'key correct used';
            } else if (game.getWrongLetters().includes(letter)) {
                key.className = 'key wrong used';
            } else if (key.classList.contains('pending')) {
                // Keep pending state for current move feedback
            } else {
                key.className = 'key';
            }
        });
    }

    /**
     * Mark key as used
     */
    markKeyUsed(keyElement, type) {
        if (type === 'correct') {
            keyElement.className = 'key correct animate-pulse';
        } else if (type === 'wrong') {
            keyElement.className = 'key wrong animate-pulse';
        }
        
        // Remove animation class after animation completes
        setTimeout(() => {
            keyElement.classList.remove('animate-pulse');
        }, 300);
    }

    /**
     * Show message
     */
    showMessage(text, type = 'info') {
        const gameStatus = document.getElementById('gameStatus');
        gameStatus.textContent = text;
        gameStatus.className = `status-message ${type}`;
        
        // Auto-hide info messages after 5 seconds
        if (type === 'info') {
            setTimeout(() => {
                if (gameStatus.textContent === text) {
                    gameStatus.textContent = '';
                    gameStatus.className = 'status-message';
                }
            }, 5000);
        }
    }

    /**
     * Switch mode
     */
    switchMode(mode) {
        // Update active button
        document.querySelectorAll('.mode-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-mode="${mode}"]`).classList.add('active');
        
        // Show corresponding content
        document.querySelectorAll('.mode-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${mode}Mode`).classList.add('active');
    }

    /**
     * Update games history
     */
    updateGamesHistory(games) {
        const historyContainer = document.getElementById('gamesHistory');
        
        if (games.length === 0) {
            historyContainer.innerHTML = '<p class="empty-message">Игр пока нет. Начните новую игру!</p>';
            return;
        }
        
        let html = '';
        
        games.forEach(game => {
            const gameDate = new Date(game.playedAt).toLocaleString('ru-RU');
            const resultClass = game.isWon ? 'won' : 'lost';
            const resultText = game.isWon ? 'ПОБЕДА' : 'ПОРАЖЕНИЕ';
            
            html += `
                <div class="game-item ${resultClass}">
                    <div class="game-header">
                        <span class="game-id">Игра #${game.id}</span>
                        <span class="game-result">${resultText}</span>
                    </div>
                    <div class="game-details">
                        <p>Игрок: ${game.playerName}</p>
                        <p>Слово: ${game.secretWord.toUpperCase()}</p>
                        <p>Попыток: ${game.attemptsCount}</p>
                        <p>Дата: ${gameDate}</p>
                    </div>
                    <div class="game-actions">
                        <button class="btn btn-sm" onclick="window.gameController.replayGame(${game.id})">
                            <i class="fas fa-play-circle"></i> Повторить
                        </button>
                    </div>
                </div>
            `;
        });
        
        historyContainer.innerHTML = html;
    }

    /**
     * Update statistics
     */
    updateStatistics(stats) {
        document.getElementById('totalGames').textContent = stats.total;
        document.getElementById('wonGames').textContent = stats.won;
        document.getElementById('lostGames').textContent = stats.lost;
        document.getElementById('winRate').textContent = `${stats.winRate}%`;
        
        // Update chart
        const total = stats.total || 1;
        const wonPercent = (stats.won / total) * 100;
        const lostPercent = (stats.lost / total) * 100;
        
        document.getElementById('wonChart').style.width = `${wonPercent}%`;
        document.getElementById('lostChart').style.width = `${lostPercent}%`;
    }

    /**
     * Update game info in sidebar
     */
    updateGameInfo(info) {
        document.getElementById('currentWordInfo').textContent = info.currentWord || '-';
        document.getElementById('guessedCount').textContent = info.guessedCount;
        document.getElementById('movesCount').textContent = info.movesCount;
        document.getElementById('gameStarted').textContent = info.gameStarted;
    }

    /**
     * Show word guess modal
     */
    showWordGuessModal() {
        const modal = document.getElementById('guessWordModal');
        const input = document.getElementById('wordGuessInput');
        
        input.value = '';
        modal.classList.add('active');
        input.focus();
        
        // Setup event listeners for modal
        const submitBtn = document.getElementById('submitWordGuess');
        const cancelBtn = document.getElementById('cancelWordGuess');
        
        const submitHandler = () => {
            const word = input.value.trim().toLowerCase();
            if (word) {
                this.controller.handleWordGuess(word);
                modal.classList.remove('active');
            }
        };
        
        const cancelHandler = () => {
            modal.classList.remove('active');
        };
        
        // Remove old listeners
        submitBtn.replaceWith(submitBtn.cloneNode(true));
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        
        // Add new listeners
        document.getElementById('submitWordGuess').addEventListener('click', submitHandler);
        document.getElementById('cancelWordGuess').addEventListener('click', cancelHandler);
        
        // Submit on Enter
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                submitHandler();
            }
        });
    }

    /**
     * Show replay modal
     */
    showReplayModal(replayData) {
        const modal = document.getElementById('replayModal');
        const content = document.getElementById('replayContent');
        
        const game = replayData.gameInfo;
        const steps = replayData.replaySteps;
        
        let html = `
            <div class="replay-info">
                <p><strong>Игрок:</strong> ${game.playerName}</p>
                <p><strong>Слово:</strong> ${game.secretWord.toUpperCase()}</p>
                <p><strong>Результат:</strong> ${game.isWon ? 'Победа' : 'Поражение'}</p>
                <p><strong>Всего попыток:</strong> ${steps.length}</p>
            </div>
            <div class="replay-steps">
                <h4>Ход игры:</h4>
        `;
        
        steps.forEach(step => {
            const resultIcon = step.isCorrect ? '✓' : '✗';
            const resultClass = step.isCorrect ? 'correct' : 'wrong';
            
            html += `
                <div class="replay-step ${resultClass}">
                    <p><strong>Попытка ${step.attemptNumber}:</strong> 
                    ${step.letter.length > 1 ? 'Слово' : 'Буква'} 
                    "<span class="replay-letter">${step.letter.toUpperCase()}</span>" 
                    - ${step.isCorrect ? 'правильно' : 'неверно'}</p>
                    <p>Слово: ${step.afterWord.replace(/ /g, ' ')}</p>
                    <p>Ошибок: ${step.mistakes}/6</p>
                </div>
            `;
        });
        
        html += `</div>`;
        content.innerHTML = html;
        
        modal.classList.add('active');
        
        // Setup close button
        document.getElementById('closeReplay').addEventListener('click', () => {
            modal.classList.remove('active');
        });
    }

    /**
     * Initialize modals
     */
    initializeModals() {
        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
        
        // Close replay modal button
        document.getElementById('closeReplay').addEventListener('click', () => {
            document.getElementById('replayModal').classList.remove('active');
        });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Physical keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key.length === 1 && /[а-яА-Яa-zA-Z]/.test(e.key)) {
                const letter = e.key.toLowerCase();
                if (this.keyboardCallback) {
                    this.keyboardCallback(letter);
                    
                    // Find and highlight the key
                    const keyElement = document.querySelector(`.key[data-letter="${letter}"]`);
                    if (keyElement) {
                        keyElement.classList.add('animate-pulse');
                        setTimeout(() => {
                            keyElement.classList.remove('animate-pulse');
                        }, 300);
                    }
                }
            }
        });
    }
}