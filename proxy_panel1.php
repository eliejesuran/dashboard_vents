<?php
// Vérifier que les identifiants ont été fournis (ici via GET, mais vous pouvez adapter pour POST)
if (isset($_GET['username']) && isset($_GET['password'])) {
    $username = $_GET['username'];
    $password = $_GET['password'];

    // L'URL de la page à afficher (accessible uniquement une fois authentifié)
    $url = "https://www.mymeteo.be/?target=incaBe/index.php";

    // Créer un fichier temporaire pour stocker les cookies
    $cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Stocker et utiliser les cookies
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    // Authentification HTTP Basic
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    // Suivre les redirections
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // Exécuter la requête
    $result = curl_exec($ch);

    // En cas d'erreur cURL, afficher l'erreur
    if (curl_errno($ch)) {
        echo "Erreur cURL : " . curl_error($ch);
    }

    curl_close($ch);

    // Afficher le résultat (le contenu de la carte)
    echo $result;

    // Supprimer le fichier temporaire de cookie
    unlink($cookieFile);
} else {
    echo "Identifiants manquants.";
}
?>
