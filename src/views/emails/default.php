<?php 
foreach ($data as $name => $value): 

if (empty($value)) {
    continue;
}

$name = explode('_', $name);
$name[0] = ucfirst($name[0]);
$name = implode(' ', $name);
?>

<?php echo $name ?>: <?php echo $value ?>

<hr>

<?php endforeach ?>