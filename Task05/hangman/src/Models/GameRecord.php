<?php

namespace DmitriyKorotkevich\Hangman\Models;

use RedBeanPHP\R;

/**
 * Game record for database operations using RedBeanPHP
 */
class GameRecord
{
    private ?int $id = null;
    private string $playerName;
    private string $word;
    private bool $isWon;
    private \DateTime $playedAt;
    private array $attempts = [];

    public function __construct(
        string $playerName,
        string $word,
        bool $isWon,
        array $attempts = []
    ) {
        $this->playerName = $playerName;
        $this->word = $word;
        $this->isWon = $isWon;
        $this->playedAt = new \DateTime();
        $this->attempts = $attempts;
        
        Database::initialize();
    }

    /**
     * Save game record to database using RedBeanPHP
     */
    public function save(): int
    {
        try {
            // Create game bean
            $game = R::dispense('game');
            $game->playerName = $this->playerName;
            $game->secretWord = $this->word;
            $game->isWon = $this->isWon;
            $game->playedAt = $this->playedAt->format('Y-m-d H:i:s');
            
            // Store game
            $this->id = R::store($game);
            
            // Save attempts
            $attemptNumber = 1;
            foreach ($this->attempts as $attempt) {
                $attemptBean = R::dispense('attempt');
                $attemptBean->gameId = $this->id;
                $attemptBean->attemptNumber = $attemptNumber++;
                $attemptBean->letter = $attempt['letter'];
                $attemptBean->isCorrect = $attempt['is_correct'];
                
                R::store($attemptBean);
            }
            
            return $this->id;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to save game: ' . $e->getMessage());
        }
    }

    /**
     * Get all saved games using RedBeanPHP
     */
    public static function getAllGames(): array
    {
        Database::initialize();
        
        $games = R::getAll("
            SELECT 
                g.id,
                g.player_name as playerName,
                g.secret_word as secretWord,
                g.is_won as isWon,
                g.played_at as playedAt,
                COUNT(a.id) as attemptsCount
            FROM game g
            LEFT JOIN attempt a ON g.id = a.game_id
            GROUP BY g.id
            ORDER BY g.played_at DESC
        ");
        
        return array_map(function($game) {
            return [
                'id' => (int)$game['id'],
                'player_name' => $game['playerName'],
                'secret_word' => $game['secretWord'],
                'is_won' => (bool)$game['isWon'],
                'played_at' => $game['playedAt'],
                'attempts_count' => (int)$game['attemptsCount']
            ];
        }, $games);
    }

    /**
     * Get game by ID with all attempts using RedBeanPHP
     */
    public static function getGameById(int $id): ?array
    {
        Database::initialize();
        
        // Get game info
        $game = R::load('game', $id);
        
        if (!$game->id) {
            return null;
        }
        
        // Get attempts
        $attempts = R::findAll('attempt', ' game_id = ? ORDER BY attempt_number', [$id]);
        
        $attemptsArray = [];
        foreach ($attempts as $attempt) {
            $attemptsArray[] = [
                'attempt_number' => (int)$attempt->attemptNumber,
                'letter' => $attempt->letter,
                'is_correct' => (bool)$attempt->isCorrect
            ];
        }
        
        return [
            'id' => (int)$game->id,
            'player_name' => $game->playerName,
            'secret_word' => $game->secretWord,
            'is_won' => (bool)$game->isWon,
            'played_at' => $game->playedAt,
            'attempts' => $attemptsArray
        ];
    }

    /**
     * Format game result for display
     */
    public static function formatGameResult(array $game): string
    {
        $result = $game['is_won'] ? 'Победа' : 'Поражение';
        $playedAt = date('d.m.Y H:i', strtotime($game['played_at']));
        
        return sprintf(
            "Игра #%d | Игрок: %s | Слово: %s | Результат: %s | Попыток: %d | Дата: %s",
            $game['id'],
            $game['player_name'],
            strtoupper($game['secret_word']),
            $result,
            $game['attempts_count'] ?? count($game['attempts'] ?? []),
            $playedAt
        );
    }

    /**
     * Format attempt result
     */
    public static function formatAttempt(array $attempt): string
    {
        $result = $attempt['is_correct'] ? '✓ правильно' : '✗ неверно';
        return sprintf(
            "  Попытка %d: буква '%s' - %s",
            $attempt['attempt_number'],
            strtoupper($attempt['letter']),
            $result
        );
    }

    /**
     * Get statistics - count of games by result
     */
    public static function getStatistics(): array
    {
        Database::initialize();
        
        $totalGames = R::count('game');
        $wonGames = R::count('game', ' is_won = ? ', [1]);
        $lostGames = R::count('game', ' is_won = ? ', [0]);
        
        return [
            'total' => $totalGames,
            'won' => $wonGames,
            'lost' => $lostGames,
            'win_rate' => $totalGames > 0 ? round($wonGames / $totalGames * 100, 2) : 0
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function isWon(): bool
    {
        return $this->isWon;
    }

    public function getPlayedAt(): \DateTime
    {
        return $this->playedAt;
    }

    public function getAttempts(): array
    {
        return $this->attempts;
    }

    /**
     * Add attempt to record
     */
    public function addAttempt(string $letter, bool $isCorrect): void
    {
        $this->attempts[] = [
            'letter' => $letter,
            'is_correct' => $isCorrect
        ];
    }
}
