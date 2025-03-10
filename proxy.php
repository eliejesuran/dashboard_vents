<?php
// Ce script proxy supporte deux modes : 
// - S'il reçoit 'username' et 'password' (via GET dans cet exemple), il effectue une requête authentifiée vers mymeteo.be.
// - Sinon, s'il reçoit un paramètre 'target', il récupère le contenu de l'URL spécifiée.
header("Access-Control-Allow-Origin: *");

if (isset($_GET['username']) && isset($_GET['password'])) {
    $username = $_GET['username'];
    $password = $_GET['password'];
    // URL du site cible pour la carte
    $url = "https://www.mymeteo.be/?target=incaBe/index.php";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Authentification HTTP Basic
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Erreur cURL : " . curl_error($ch);
    }
    curl_close($ch);
    echo $result;

} elseif (isset($_GET['target'])) {
    $target = $_GET['target'];
    // Vous pouvez ajouter ici une validation pour autoriser seulement certains domaines
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $target);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Erreur cURL : " . curl_error($ch);
    }
    curl_close($ch);
    echo $result;

} else {
    echo "Identifiants ou paramètre target manquant.";
}
?>
