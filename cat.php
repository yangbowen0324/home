<?PHP
$birthday = '2015-01-29';
$birTime = strtotime($birthday);
$curTime = time();
$total = (time() - $birTime ) / (3600 * 24);
ECHO $total."\n";die;
