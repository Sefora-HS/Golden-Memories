<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navigation">
<ul>

<!-- albums -->
<li class="list <?= ($current_page == 'index.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'index.php') ? '#' : 'index.php' ?>">
        <span class="icon"><ion-icon name="images-outline"></ion-icon></span>
        <p>Albums</p>
    </a>
</li>

<!-- explorer -->
<li class="list <?= ($current_page == 'explore.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'explore.php') ? '#' : './vue/pages/explore.php' ?>">
        <span class="icon"><ion-icon name="shuffle-outline"></ion-icon></span>
        <p>Explorer</p>
    </a>
</li>

<!-- ajouter -->
<li class="list">
    <a href="#">
        <span class="icon"><ion-icon name="add-circle" class="circle"></ion-icon></span>
    </a>
</li>

<!-- partages -->
<li class="list <?= ($current_page == 'partages.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'partages.php') ? '#' : 'partages.php' ?>">
        <span class="icon"><ion-icon name="people-outline"></ion-icon></span>
        <p>Partages</p>
    </a>
</li>

<!-- capsules -->
<li class="list <?= ($current_page == 'capsules.php') ? 'active' : '' ?>">
    <a href="<?= ($current_page == 'capsules.php') ? '#' : 'capsules.php' ?>">
        <span class="icon"><ion-icon name="time-outline"></ion-icon></span>
        <p>Capsules</p>
    </a>
</li>

<div class="indicator"></div>

</ul>
</nav>