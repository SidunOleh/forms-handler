<table role="presentation" style="width:602px;border-collapse:collapse;border:1px solid #cccccc;border-spacing:0;text-align:left;">

<?php 
foreach ($this->data as $name => $value): 

$name = explode('_', $name);
$name[0] = ucfirst($name[0]);
$name = implode(' ', $name);

if (is_array($value)) {
    $value = implode(', ', $value);
}
?>

<tr>
    <td style="padding-left:2px;border:1px solid #cccccc;">
        <?php echo $name ?>
    </td>
    <td style="padding-left:2px;border:1px solid #cccccc;">
        <?php echo nl2br($value) ?>
    </td>
</tr>

<?php endforeach ?>

</table>