/**
 * Database module for IndexedDB operations
 * Аналог Database.php из PHP версии
 */
export class Database {
    static DB_NAME = 'hangman_db';
    static DB_VERSION = 1;
    static db = null;

    /**
     * Initialize database connection
     */
    static async initialize() {
        return new Promise((resolve, reject) => {
            if (this.db) {
                resolve(this.db);
                return;
            }

            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onerror = (event) => {
                console.error('Database error:', event.target.error);
                reject(event.target.error);
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create games store
                if (!db.objectStoreNames.contains('games')) {
                    const gamesStore = db.createObjectStore('games', { 
                        keyPath: 'id',
                        autoIncrement: true 
                    });
                    gamesStore.createIndex('playedAt', 'playedAt', { unique: false });
                    gamesStore.createIndex('playerName', 'playerName', { unique: false });
                    gamesStore.createIndex('isWon', 'isWon', { unique: false });
                }

                // Create attempts store
                if (!db.objectStoreNames.contains('attempts')) {
                    const attemptsStore = db.createObjectStore('attempts', { 
                        keyPath: 'id',
                        autoIncrement: true 
                    });
                    attemptsStore.createIndex('gameId', 'gameId', { unique: false });
                    attemptsStore.createIndex('attemptNumber', 'attemptNumber', { unique: false });
                }

                // Create words store
                if (!db.objectStoreNames.contains('words')) {
                    const wordsStore = db.createObjectStore('words', { 
                        keyPath: 'id',
                        autoIncrement: true 
                    });
                    wordsStore.createIndex('word', 'word', { unique: true });
                }
            };
        });
    }

    /**
     * Get random word from database
     */
    static async getRandomWord() {
        await this.initialize();
        
        const defaultWords = [
            'python', 'golang', 'kotlin', 'ruby', 'java',
            'swift', 'rust', 'scala', 'perl', 'basic',
            'clojure', 'erlang', 'haskell', 'pascal', 'julia'
        ];

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['words'], 'readonly');
            const store = transaction.objectStore('words');
            const request = store.getAll();

            request.onsuccess = (event) => {
                const words = event.target.result;
                
                if (words.length === 0) {
                    // Initialize with default words
                    const initTransaction = this.db.transaction(['words'], 'readwrite');
                    const initStore = initTransaction.objectStore('words');
                    
                    defaultWords.forEach(word => {
                        initStore.add({ word: word.toLowerCase() });
                    });
                    
                    // Return random default word
                    const randomIndex = Math.floor(Math.random() * defaultWords.length);
                    resolve(defaultWords[randomIndex]);
                } else {
                    const randomIndex = Math.floor(Math.random() * words.length);
                    resolve(words[randomIndex].word);
                }
            };

            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Save game to database
     */
    static async saveGame(gameData) {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games', 'attempts'], 'readwrite');
            
            // Save game
            const gamesStore = transaction.objectStore('games');
            const gameRequest = gamesStore.add(gameData);

            gameRequest.onsuccess = (event) => {
                const gameId = event.target.result;
                
                // Save attempts if provided
                if (gameData.attempts && gameData.attempts.length > 0) {
                    const attemptsStore = transaction.objectStore('attempts');
                    
                    gameData.attempts.forEach((attempt, index) => {
                        attemptsStore.add({
                            gameId: gameId,
                            attemptNumber: index + 1,
                            letter: attempt.letter,
                            isCorrect: attempt.isCorrect,
                            timestamp: new Date().toISOString()
                        });
                    });
                }
                
                resolve(gameId);
            };

            gameRequest.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Get all games from database
     */
    static async getAllGames() {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games'], 'readonly');
            const store = transaction.objectStore('games');
            const index = store.index('playedAt');
            const request = index.getAll();

            request.onsuccess = async (event) => {
                const games = event.target.result;
                
                // Get attempts count for each game
                for (let game of games) {
                    game.attemptsCount = await this.getAttemptsCount(game.id);
                }
                
                resolve(games.reverse()); // Newest first
            };

            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Get game by ID with attempts
     */
    static async getGameById(gameId) {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games', 'attempts'], 'readonly');
            
            // Get game
            const gamesStore = transaction.objectStore('games');
            const gameRequest = gamesStore.get(gameId);

            gameRequest.onsuccess = async (event) => {
                const game = event.target.result;
                
                if (!game) {
                    resolve(null);
                    return;
                }
                
                // Get attempts
                const attemptsStore = transaction.objectStore('attempts');
                const attemptsIndex = attemptsStore.index('gameId');
                const attemptsRequest = attemptsIndex.getAll(gameId);

                attemptsRequest.onsuccess = (event) => {
                    game.attempts = event.target.result.sort((a, b) => a.attemptNumber - b.attemptNumber);
                    resolve(game);
                };

                attemptsRequest.onerror = (event) => {
                    reject(event.target.error);
                };
            };

            gameRequest.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Get attempts count for game
     */
    static async getAttemptsCount(gameId) {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['attempts'], 'readonly');
            const store = transaction.objectStore('attempts');
            const index = store.index('gameId');
            const request = index.count(gameId);

            request.onsuccess = (event) => {
                resolve(event.target.result);
            };

            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Get game statistics
     */
    static async getStatistics() {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games'], 'readonly');
            const store = transaction.objectStore('games');
            const request = store.getAll();

            request.onsuccess = (event) => {
                const games = event.target.result;
                const total = games.length;
                const won = games.filter(game => game.isWon).length;
                const lost = total - won;
                const winRate = total > 0 ? ((won / total) * 100).toFixed(1) : 0;

                resolve({
                    total,
                    won,
                    lost,
                    winRate
                });
            };

            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Clear all games
     */
    static async clearGames() {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games', 'attempts'], 'readwrite');
            
            transaction.objectStore('games').clear();
            transaction.objectStore('attempts').clear();

            transaction.oncomplete = () => {
                resolve();
            };

            transaction.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Add word to database
     */
    static async addWord(word) {
        await this.initialize();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['words'], 'readwrite');
            const store = transaction.objectStore('words');
            const request = store.add({ word: word.toLowerCase() });

            request.onsuccess = (event) => {
                resolve(event.target.result);
            };

            request.onerror = (event) => {
                // Word might already exist
                if (event.target.error.name === 'ConstraintError') {
                    resolve(null);
                } else {
                    reject(event.target.error);
                }
            };
        });
    }

    /**
     * Close database connection
     */
    static close() {
        if (this.db) {
            this.db.close();
            this.db = null;
        }
    }
}
