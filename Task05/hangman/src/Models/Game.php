<?php

namespace DmitriyKorotkevich\Hangman\Models;

/**
 * Game model for Hangman
 */
class Game
{
    private string $word;
    private array $guessedLetters = [];
    private array $wrongLetters = [];
    private int $maxAttempts = 6;
    private int $attemptsLeft;
    private string $playerName;
    private bool $isFinished = false;
    private bool $isWon = false;
    private int $mistakes = 0;
    private GameRecord $gameRecord;
    private array $attemptHistory = [];

    public function __construct(string $playerName = 'Player')
    {
        $this->playerName = $playerName;
        $this->word = Database::getRandomWord();
        $this->attemptsLeft = $this->maxAttempts;
        
        // Initialize game record
        $this->gameRecord = new GameRecord($playerName, $this->word, false);
    }

    /**
     * Process guess
     */
    public function guess(string $input): array
    {
        $input = strtolower(trim($input));
        
        if (strlen($input) > 1) {
            return $this->guessWord($input);
        }
        
        return $this->guessLetter($input);
    }

    private function guessLetter(string $letter): array
    {
        // Check if already guessed
        if (in_array($letter, $this->guessedLetters) || in_array($letter, $this->wrongLetters)) {
            return ['success' => false, 'message' => "Буква '$letter' уже была"];
        }

        $isCorrect = strpos($this->word, $letter) !== false;
        
        // Record attempt
        $this->gameRecord->addAttempt($letter, $isCorrect);
        $this->attemptHistory[] = [
            'letter' => $letter,
            'is_correct' => $isCorrect,
            'timestamp' => date('H:i:s')
        ];

        if ($isCorrect) {
            $this->guessedLetters[] = $letter;
            
            // Check for win
            if ($this->isWordGuessed()) {
                $this->finishGame(true);
            }
            
            return ['success' => true, 'letter' => $letter, 'position' => 'correct'];
        }

        $this->wrongLetters[] = $letter;
        $this->mistakes++;
        $this->attemptsLeft--;

        if ($this->attemptsLeft <= 0) {
            $this->finishGame(false);
        }

        return ['success' => true, 'letter' => $letter, 'position' => 'wrong'];
    }

    private function guessWord(string $word): array
    {
        $isCorrect = $word === $this->word;
        
        // Record word attempt
        $this->gameRecord->addAttempt($word, $isCorrect);
        $this->attemptHistory[] = [
            'letter' => $word,
            'is_correct' => $isCorrect,
            'timestamp' => date('H:i:s')
        ];

        if ($isCorrect) {
            $this->guessedLetters = array_unique(str_split($this->word));
            $this->finishGame(true);
            return ['success' => true, 'message' => 'Вы угадали слово!'];
        }

        $this->attemptsLeft = 0;
        $this->finishGame(false);
        $this->mistakes = $this->maxAttempts;
        
        return ['success' => false, 'message' => 'Неправильное слово'];
    }

    /**
     * Finish game and save to database
     */
    private function finishGame(bool $isWon): void
    {
        $this->isFinished = true;
        $this->isWon = $isWon;
        
        // Update game record with final state
        $finalRecord = new GameRecord(
            $this->playerName,
            $this->word,
            $isWon,
            array_map(function($attempt) {
                return [
                    'letter' => $attempt['letter'],
                    'is_correct' => $attempt['is_correct']
                ];
            }, $this->attemptHistory)
        );
        
        // Save to database
        try {
            $finalRecord->save();
        } catch (\Exception $e) {
            // Log error but don't break game
            error_log('Failed to save game: ' . $e->getMessage());
        }
    }

    /**
     * Get current word state
     */
    public function getCurrentWord(): string
    {
        $result = '';
        
        for ($i = 0; $i < strlen($this->word); $i++) {
            $letter = $this->word[$i];
            $result .= in_array($letter, $this->guessedLetters) ? strtoupper($letter) . ' ' : '_ ';
        }
        
        return trim($result);
    }

    private function isWordGuessed(): bool
    {
        for ($i = 0; $i < strlen($this->word); $i++) {
            if (!in_array($this->word[$i], $this->guessedLetters)) {
                return false;
            }
        }
        
        return true;
    }

    // Getters
    public function getWord(): string
    {
        return $this->word;
    }

    public function getGuessedLetters(): array
    {
        return $this->guessedLetters;
    }

    public function getWrongLetters(): array
    {
        return $this->wrongLetters;
    }

    public function getAttemptsLeft(): int
    {
        return $this->attemptsLeft;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function isWon(): bool
    {
        return $this->isWon;
    }

    public function getMistakes(): int
    {
        return $this->mistakes;
    }

    public function getGameRecord(): GameRecord
    {
        return $this->gameRecord;
    }

    public function getAttemptHistory(): array
    {
        return $this->attemptHistory;
    }
}
