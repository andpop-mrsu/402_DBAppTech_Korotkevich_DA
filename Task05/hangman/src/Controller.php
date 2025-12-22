<?php

namespace DmitriyKorotkevich\Hangman;

use DmitriyKorotkevich\Hangman\Models\Game;
use DmitriyKorotkevich\Hangman\Models\GameRecord;
use DmitriyKorotkevich\Hangman\Models\Database;
use function cli\line;
use function cli\prompt;

class Controller
{
    private View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public static function run(array $argv): void
    {
        $controller = new self();
        $controller->start($argv);
    }

    public function start(array $argv): void
    {
        $options = getopt("nlr:sh", ["new", "list", "replay:", "stats", "help"]);

        if (isset($options['h']) || isset($options['help'])) {
            $this->view->showHelp();
            return;
        }

        if (isset($options['s']) || isset($options['stats'])) {
            $this->showStatistics();
            return;
        }

        if (isset($options['l']) || isset($options['list'])) {
            $this->showGameList();
            return;
        }

        if (isset($options['r']) || isset($options['replay'])) {
            $id = $options['r'] ?? $options['replay'];
            $this->replayGame((int)$id);
            return;
        }

        $this->startNewGame();
    }

    private function startNewGame(): void
    {
        $this->view->showWelcome();
        
        $playerName = prompt("Введите ваше имя", "Player");
        $game = new Game($playerName);
        
        $this->playGame($game);
    }

    private function playGame(Game $game): void
    {
        while (!$game->isFinished()) {
            $this->view->showGameState(
                $game->getCurrentWord(),
                $game->getWrongLetters(),
                $game->getAttemptsLeft(),
                $game->getMistakes()
            );

            $input = strtolower(trim(prompt("Введите букву или слово")));

            if ($input === 'exit' || $input === 'quit') {
                $this->view->showMessage("Игра прервана.");
                return;
            }

            if (empty($input)) {
                continue;
            }

            $result = $game->guess($input);

            if (!$result['success'] && isset($result['message'])) {
                $this->view->showError($result['message']);
            }
        }

        $this->showGameResult($game);
    }

    private function showGameResult(Game $game): void
    {
        $this->view->showGameOver(
            $game->isWon(),
            $game->getWord(),
            $game->getMistakes()
        );

        // Show ORM info
        $this->view->showMessage("Игра сохранена через RedBeanPHP ORM");
    }

    private function showGameList(): void
    {
        try {
            $games = GameRecord::getAllGames();
            
            if (empty($games)) {
                $this->view->showMessage("В базе данных пока нет сохраненных игр.");
                return;
            }

            $this->view->showGameListHeader();
            
            foreach ($games as $game) {
                $this->view->showGameListItem($game);
            }
            
            $this->view->showMessage("\nДля повторения игры используйте: hangman --replay ID");
            $this->view->showMessage("Для статистики: hangman --stats");
            
        } catch (\Exception $e) {
            $this->view->showError("Ошибка при получении списка игр: " . $e->getMessage());
        }
    }

    private function showStatistics(): void
    {
        try {
            $stats = GameRecord::getStatistics();
            
            $this->view->showStatisticsHeader();
            $this->view->showStatistics($stats);
            
        } catch (\Exception $e) {
            $this->view->showError("Ошибка при получении статистики: " . $e->getMessage());
        }
    }

    private function replayGame(int $gameId): void
    {
        try {
            $gameData = GameRecord::getGameById($gameId);
            
            if (!$gameData) {
                $this->view->showError("Игра #$gameId не найдена.");
                return;
            }

            $this->view->showReplayHeader($gameData);
            
            // Simulate the game
            $word = $gameData['secret_word'];
            $guessed = [];
            $mistakes = 0;
            
            foreach ($gameData['attempts'] as $attempt) {
                $letter = $attempt['letter'];
                $isCorrect = (bool)$attempt['is_correct'];
                
                $this->view->showReplayAttempt(
                    $attempt['attempt_number'],
                    $letter,
                    $isCorrect
                );
                
                if (strlen($letter) === 1) {
                    if ($isCorrect) {
                        $guessed[] = $letter;
                    } else {
                        $mistakes++;
                    }
                }
                
                // Show current word state
                $currentWord = '';
                foreach (str_split($word) as $char) {
                    $currentWord .= in_array($char, $guessed) ? strtoupper($char) . ' ' : '_ ';
                }
                
                $this->view->showWordState(trim($currentWord));
                $this->view->showHangman($mistakes);
                
                // Pause between attempts for better visualization
                sleep(1);
            }
            
            $this->view->showReplayResult($gameData);
            
        } catch (\Exception $e) {
            $this->view->showError("Ошибка при повторении игры: " . $e->getMessage());
        }
    }
}
