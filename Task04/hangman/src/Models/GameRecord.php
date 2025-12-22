<?php

namespace DmitriyKorotkevich\Hangman\Models;

use PDO;

/**
 * Game record for database operations
 */
class GameRecord
{
    private int $id;
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
    }

    /**
     * Save game record to database
     */
    public function save(): int
    {
        $db = Database::getConnection();
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Save game
            $stmt = $db->prepare("
                INSERT INTO games (player_name, secret_word, is_won, played_at) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->playerName,
                $this->word,
                $this->isWon ? 1 : 0,
                $this->playedAt->format('Y-m-d H:i:s')
            ]);
            
            $this->id = (int)$db->lastInsertId();
            
            // Save attempts
            $attemptStmt = $db->prepare("
                INSERT INTO attempts (game_id, attempt_number, letter, is_correct)
                VALUES (?, ?, ?, ?)
            ");
            
            $attemptNumber = 1;
            foreach ($this->attempts as $attempt) {
                $attemptStmt->execute([
                    $this->id,
                    $attemptNumber++,
                    $attempt['letter'],
                    $attempt['is_correct'] ? 1 : 0
                ]);
            }
            
            $db->commit();
            return $this->id;
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw new \RuntimeException('Failed to save game: ' . $e->getMessage());
        }
    }

    /**
     * Get all saved games
     */
    public static function getAllGames(): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->query("
            SELECT 
                g.id,
                g.player_name,
                g.secret_word,
                g.is_won,
                g.played_at,
                COUNT(a.id) as attempts_count
            FROM games g
            LEFT JOIN attempts a ON g.id = a.game_id
            GROUP BY g.id
            ORDER BY g.played_at DESC
        ");
        
        return $stmt->fetchAll();
    }

    /**
     * Get game by ID with all attempts
     */
    public static function getGameById(int $id): ?array
    {
        $db = Database::getConnection();
        
        // Get game info
        $stmt = $db->prepare("
            SELECT * FROM games 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return null;
        }
        
        // Get attempts
        $attemptStmt = $db->prepare("
            SELECT attempt_number, letter, is_correct 
            FROM attempts 
            WHERE game_id = ? 
            ORDER BY attempt_number
        ");
        $attemptStmt->execute([$id]);
        $attempts = $attemptStmt->fetchAll();
        
        $game['attempts'] = $attempts;
        return $game;
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
            $game['attempts_count'] ?? 0,
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

    // Getters
    public function getId(): int
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
