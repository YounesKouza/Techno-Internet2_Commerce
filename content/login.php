<?php
if (isset($_POST['login']) && isset($_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];

    $user = new UserDAO($cnx);
    $user = $user->getUser($login, $password);

    if ($user) {
        $_SESSION['user'] = $user;
        header('Location: index.php');
    } else {
        print "Login ou mot de passe incorrect.";
    }
}
?>
<form action="<?php print $_SERVER['PHP_SELF']; ?>" method="post">
    <div class="mb-3">
        <label for="login" class="form-label">Login</label>
        <input type="text" class="form-control" id="login" name="login">
        <input type="password" class="form-control" id="password" name="password">
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password1" name="password">
    </div>
    <button type="submit" class="btn btn-primary">Connexion</button>
</form>
