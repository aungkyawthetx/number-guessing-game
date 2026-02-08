<?php
    session_start();
    $message = "";
    $new_game = "";
    $hint = "";
    $feedback_class = "";
    $min = 1;
    $max = 100;

    function resetGame() {
        unset($_SESSION['secret_number'], $_SESSION['guess_count'], $_SESSION['last_guess']);
    }

    if(isset($_GET['action']) && $_GET['action'] == 'reset') {
        resetGame();
        header("Location: index.php");
        exit;
    }

    if(!isset($_SESSION['secret_number'])) {
        // default difficulty: medium
        if(!isset($_SESSION['difficulty'])) {
            $_SESSION['difficulty'] = 'medium';
        }

        // set attempts by difficulty
        switch($_SESSION['difficulty']) {
            case 'easy':
                $max_attempts = 10;
                break;
            case 'hard':
                $max_attempts = 4;
                break;
            default:
                $max_attempts = 6;
        }

        $_SESSION['max_attempts'] = $max_attempts;
        $_SESSION['secret_number'] = rand($min, $max);
        $_SESSION['guess_count'] = 0;
        $_SESSION['last_guess'] = null;
        $_SESSION['guesses'] = [];
        $new_game = "New game! I've thought a number from {$min} to {$max}. ({$_SESSION['difficulty']} mode, {$max_attempts} attempts)";
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // difficulty change
        if(isset($_POST['difficulty']) && in_array($_POST['difficulty'], ['easy','medium','hard'])) {
            $_SESSION['difficulty'] = $_POST['difficulty'];
            // reset the game when difficulty changed
            unset($_SESSION['secret_number']);
            header('Location: index.php');
            exit;
        }

        if(isset($_POST['guess'])) {
            $guess = (int) $_POST['guess'];
            $secret_number = $_SESSION['secret_number'];

            // increment only if game still active
            if(isset($_SESSION['secret_number'])) {
                $_SESSION['guess_count']++;
                $_SESSION['guesses'][] = $guess;
            }

            if($guess == $secret_number) {
                $tries = $_SESSION['guess_count'];
                $message = "You nailed it in {$tries} ".($tries==1? 'try':'tries')."!";
                $feedback_class = "win";

                // update best score (fewest tries)
                if(!isset($_SESSION['best_score']) || $tries < $_SESSION['best_score']) {
                    $_SESSION['best_score'] = $tries;
                }

                unset($_SESSION['secret_number']);
            }
            else {
                $diff = abs($secret_number - $guess);
                if($guess < $secret_number) {
                    $message = "Too low!";
                    $feedback_class = "too-low";
                } else {
                    $message = "Too high!";
                    $feedback_class = "too-high";
                }

                // closeness hints
                if($diff <= 2) {
                    $hint = "Boiling! You're inches away.";
                } elseif($diff <= 5) {
                    $hint = "Very hot";
                } elseif($diff <= 10) {
                    $hint = "Warm";
                } elseif($diff <= 20) {
                    $hint = "Cold";
                } else {
                    $hint = "Freezing";
                }

                // check attempts exhausted
                if($_SESSION['guess_count'] >= $_SESSION['max_attempts']) {
                    $message = "Out of attempts — the number was {$secret_number}.";
                    $feedback_class = "lost";
                    unset($_SESSION['secret_number']);
                }
            }
            $_SESSION['last_guess'] = $guess;
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUESSONITT</title>
    <link rel="stylesheet" href="./style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Stack+Sans+Headline:wght@200..700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
        font-family: "Stack Sans Headline", sans-serif;
        font-optical-sizing: auto;
        font-weight: 300;
        font-style: normal;
        color: #ffffff;
        background: radial-gradient(circle at top left, #4c1d95, #1e1b4b);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        position: relative;
        overflow: hidden;
        }

        /* Blurred, liquid-glass overlay */
        body::before {
        content: "";
        position: absolute;
        top: -10%;
        left: -10%;
        width: 120%;
        height: 120%;
        background: 
            radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15), transparent 70%),
            radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.1), transparent 70%),
            linear-gradient(to bottom right, rgba(91, 33, 182, 0.6), rgba(59, 7, 100, 0.6));
        filter: blur(40px);
        z-index: -1;
        }

        .glass-card {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 2rem;
        backdrop-filter: blur(20px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        text-align: center;
        }

        .game-card {
            width: 100%;
            max-width: 450px;
            -webkit-backdrop-filter: blur(12px);
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .feedback-message {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .feedback-message.too-high {
            color: #f87171;
        }

        .feedback-message.too-low {
            color: #60a5fa;
        }

        .feedback-message.win {
            font-size: 2.4rem;
            color: #4ade80;
        }

        .hint-message {
            font-size: 1.125rem;
            color: #fde047;
            margin-bottom: 1.5rem;
            min-height: 1.5em;
        }

        .difficulty {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .diff-btn {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .diff-btn:hover { transform: translateY(-2px); background: rgba(255,255,255,0.09);} 

        .guesses { margin-top: 1rem; text-align: left; }
        .guesses ul { list-style: none; display:flex; gap:0.5rem; flex-wrap:wrap; padding:0; margin-top:0.5rem }
        .guesses li { background: rgba(255,255,255,0.06); padding:0.4rem 0.6rem; border-radius:8px; font-weight:700 }

        .best-score { margin-top: 0.75rem; color:#e6fffa }

        .feedback-message.lost { color: #fb7185 }

        .guess-count {
            font-size: 1.25rem;
            margin: 20px;
        }

        .guess-count strong {
            font-weight: 700;
        }

        .game-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .game-form label {
            text-align: left;
            font-size: 0.875rem;
            font-weight: 500;
            color: #e5e7eb;
        }

        .game-form input[type="number"] {
            width: 100%;
            padding: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            color: #ffffff;

            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .game-form input[type="number"]:focus {
            border-color: #a78bfa;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .game-button {
            width: 100%;
            padding: 0.85rem 1rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: #ffffff;

            background-color: #7c3aed;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;

            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .game-button:hover {
            background-color: #6d28d9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .game-button.play-again {
            background-color: #22c55e;
            text-decoration: none;
            margin-top: 1rem;
        }

        .game-button.play-again:hover {
            background-color: #16a34a;
        }

        .reset-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .reset-link a {
            color: #d1d5db;
            font-size: 0.875rem;
            text-decoration: underline;
            transition: color 0.2s ease;
        }

        .reset-link a:hover {
            color: #ffffff;
        }

        .play-again-button {
            display: none;
        }
    </style>
</head>
<body>
    <div class="game-card">
        <h1>GUESSONITT</h1>
            <p> <?= $new_game ?> </p>

        <form class="difficulty-form" method="POST" action="index.php">
            <label for="difficulty">Difficulty:</label>
            <div class="difficulty">
                <button type="submit" name="difficulty" value="easy" class="diff-btn">Easy</button>
                <button type="submit" name="difficulty" value="medium" class="diff-btn">Medium</button>
                <button type="submit" name="difficulty" value="hard" class="diff-btn">Hard</button>
            </div>
        </form>

        <?php if($feedback_class != ""): ?>
            <h2 class="feedback-message <?= $feedback_class ?>"> <?= $message ?> </h2>
        <?php else : ?>
            <p class="general-message"> <?= $message ?> </p>
        <?php endif ?>

        <p class="guess-count">Attempt: <strong> <?= $_SESSION['guess_count'] ?? 0 ?> / <?= $_SESSION['max_attempts'] ?? '-' ?> </strong></p>

        <?php if(!empty($hint)): ?>
            <p class="hint-message"> <?= htmlspecialchars($hint) ?> </p>
        <?php endif ?>

        <?php if(isset($_SESSION['secret_number'])): ?>
            <form class="game-form" action="index.php" method="POST">
                <label for="guess"> Enter your guess (<?= $min ?> - <?= $max ?>) </label>
                <input type="number" id="guess" name="guess" min="<?= $min ?>" max="<?= $max ?>" placeholder="?" required autofocus>
                <button type="submit" class="game-button" name="btn-guess">Guess</button>
            </form>
        <?php else: ?>
            <a href="?action=reset" class="game-button play-again">Play Again?</a>
        <?php endif ?>

        <?php if(!empty($_SESSION['guesses'])): ?>
            <div class="guesses">
                <strong>Previous guesses:</strong>
                <ul>
                    <?php foreach($_SESSION['guesses'] as $g): ?>
                        <li><?= htmlspecialchars($g) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <?php if(isset($_SESSION['best_score'])): ?>
            <p class="best-score">Best score: <strong><?= $_SESSION['best_score'] ?> <?= $_SESSION['best_score']==1? 'try':'tries' ?></strong></p>
        <?php endif ?>

        <div class="reset-link">
            <a href="?action=reset">Start a New Game</a>
        </div>
    </div>
</body>
</html>