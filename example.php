<?php

// Поиск в строке, начина с конца, после первой "|".
$string = "15|234|12|2023";
$search = "/\|(0[1-9]|1[0-2])\|/";
$replacement = "|replacement|";

$result = preg_replace($search, $replacement, $string);

echo $result;
