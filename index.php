<?php
    session_start();
    $message = "";
    $new_game = "";
    $hint = "";
    $feedback_class = "";

    function resetGame() {
        unset($_SESSION['secret_number'], $_SESSION['guess_count'], $_SESSION['last_guess']);
    }

    if(isset($_GET['action']) && $_GET['action'] == 'reset') {
        resetGame();
        header("Location: index.php");
        exit;
    }

    if(!isset($_SESSION['secret_number'])) {
        $_SESSION['secret_number'] = rand(1, 10);
        $_SESSION['guess_count'] = 0;
        $_SESSION['last_guess'] = null;
        $new_game = "New game! I've thought a number from 1 to 10.";
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guess'])) {
        $guess = (int) $_POST['guess'];
        $_SESSION['guess_count']++;
        $secret_number = $_SESSION['secret_number'];

        if($guess == $secret_number) {
            $message = "Congratulations! You guessed it in {$_SESSION['guess_count']} tries!";
            $feedback_class = "win";
            unset($_SESSION['secret_number']);
            unset($_SESSION['last_guess']);
        }
        elseif($guess < $secret_number) {
            $message = "Too low!";
            $feedback_class = "too-low";
        }
        elseif($guess > $secret_number) {
            $message = "Too high!";
            $feedback_class = "too-high";
        }
        $_SESSION['last_guess'] = $guess;
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUESSONITT</title>
    <link rel="stylesheet" href="./style.css?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Stack+Sans+Headline:wght@200..700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="game-card">
        <h1>GUESSONITT</h1>
            <p> <?= $new_game ?> </p>
        
        <?php if($feedback_class != ""): ?>
            <h2 class="feedback-message <?= $feedback_class ?>"> <?= $message ?> </h2>
        <?php else : ?>
            <p class="general-message"> <?= $message ?> </p>
        <?php endif ?>

        <p class="guess-count">Guess: <strong> <?= $_SESSION['guess_count'] ?? 0 ?> </strong></p>
        <?php if(isset($_SESSION['secret_number'])): ?>
            <form class="game-form" action="index.php" method="POST">
                <label for="guess"> Enter your guess (1 - 10) </label>
                <input type="number" id="guess" name="guess" min="1" max="10" placeholder="?" required autofocus>
                <button type="submit" class="game-button" name="btn-guess">
                    Guess
                </button>
            </form>
        <?php else: ?>
            <a href="?action=reset" class="game-button play-again">
                Play Again?
            </a>
        <?php endif ?>

        <div class="reset-link">
            <a href="?action=reset">
                Start a New Game
            </a>
        </div>
    </div>
</body>
</html>