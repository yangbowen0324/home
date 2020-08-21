<?PHP
class mapWayHelper {
	public $way = [];// 路径
	public $startStep = '1-1';// 初始坐标
	public $step = 15;// 步数
	public $xMax = 6;// 横坐标最长数
	public $yMax = 6;// 纵坐标最长数

	public function __construct(){

	}

	public function buildMap(){
		// 生成节点路径
		$this->way[] = $this->startStep;
		$this->findWay($this->startStep);
	}

	// 发现路径
	private function findWay($currentStep) 
	{
		if (count($this->way) < $this->step ) {
			// 当前步骤
			list($x, $y) = explode('-', $currentStep);
			// 预期四个方向
			$direction = [
				'up' => $x . '-' . ($y+1),
				'down' => $x . '-' . ($y-1),
				'left' => ($x-1) . '-' . $y,
				'right' => ($x+1) . '-' . $y,
			];
			if ($y == $this->yMax) unset($direction['up']);
			if ($y == 1) unset($direction['down']);
			if ($x == 1) unset($direction['left']);
			if ($x == $this->xMax) unset($direction['right']);
			// 重置为索引数组
			$direction = array_values($direction);

			// 全部预期都已存在，则抛弃此线路 (围困) {{{
			if ( count(array_intersect($direction, $this->way)) == count($direction) ) ($this->way = []) && ($this->way[] = $this->startStep);
			// }}}

			// 随机下一个有效步骤
			$nextStep = $direction[array_rand($direction, 1)];

			// 有效步骤则保存
			if (!in_array($nextStep, $this->way))
			{
				$this->way[] = $nextStep;
				$this->findWay($nextStep);
			}else {
			// 无效步骤继续
				$this->findWay($currentStep);
			}
		}
	}

	// 渲染路径
	// 
}

$t1 = microtime(true);
$mapWayHelper = new mapWayHelper();
$mapWayHelper->buildMap(15);
$alreadyLocation = $mapWayHelper->way;

            $step_gater = $alreadyLocation;
            for ($y = 6; $y >= 1; $y--) { 
                for ($x = 1; $x <= 6 ; $x++) { 
                    $x_y = $x. '-' . $y;
                    if (in_array($x_y, $step_gater)) {
                        $step = array_search($x_y, $step_gater)+1;
                    } else {
                        $step = '0';
                    }
                    echo str_pad($step, 2, 0, STR_PAD_LEFT) . ' ';
                }
                echo PHP_EOL;
            }

        $t2 = microtime(true);
echo '耗时'.round($t2-$t1, 5).'秒<br>';
echo 'Now memory_get_usage: ' . memory_get_usage() . '<br />';
var_dump($valid_gather);die;