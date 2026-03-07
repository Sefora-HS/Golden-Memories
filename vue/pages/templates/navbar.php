<?php
include_once __DIR__ . '/../../../modele/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navigation">
<ul>

<li class="list <?= ($current_page == 'albums.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'albums.php') ? '#' : BASE_URL . 'albums.php' ?>">
        <span class="icon"><ion-icon name="images-outline"></ion-icon></span>
        <p>Albums</p>
    </a>
</li>

<li class="list <?= ($current_page == 'explore.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'explore.php') ? '#' : BASE_URL . 'vue/pages/explore.php' ?>">
        <span class="icon"><ion-icon name="shuffle-outline"></ion-icon></span>
        <p>Explorer</p>
    </a>
</li>

<li class="list">
    <a href="#">
        <span class="icon"><ion-icon name="add-circle" class="circle"></ion-icon></span>
    </a>
</li>

<li class="list <?= ($current_page == 'partages.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'partages.php') ? '#' : BASE_URL . 'partages.php' ?>">
        <span class="icon"><ion-icon name="people-outline"></ion-icon></span>
        <p>Partages</p>
    </a>
</li>

<li class="list <?= ($current_page == 'capsules.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'capsules.php') ? '#' : BASE_URL . 'capsules.php' ?>">
        <span class="icon"><ion-icon name="time-outline"></ion-icon></span>
        <p>Capsules</p>
    </a>
</li>

<div class="indicator"></div>

</ul>
</nav>