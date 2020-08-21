<?php

namespace console\controllers;

use yii\db\Expression;
use common\models\Book; // 小说列表
use common\models\BookChapter; // 章节列表

class BookImportController extends BaseController
{
	public static $title = ''; // 递归：全局变量 ，标题用
	public static $isEnd = false; // 递归：是否下一章
	public static $list = []; // 小说【标题、章节、内容】list

	public static $reg = ''; // 标题匹配
	public static $regNoise = []; // 内容-噪音清理
	public static $regBeatifulTitle = []; // 标题-美化
	public static $regBeatifulContent = []; // 内容-美化
	public static $chapter = 1; // 章节数

	public function actionRun($novelDir = './novel', $sex = 2)
	{
		foreach (scandir($novelDir) as $fileName) {
			if ($fileName == '.' ||  $fileName == '..' || $fileName == '.DS_Store' || $fileName == 'wait' || $fileName == 'success' || $fileName == 'error') continue;

			// 一部小说
			$title = $content = '';
			$handle = fopen($novelDir . '/' . $fileName, 'r');

			// 查小说是否存在
			$bookName = str_replace('.txt', '', $fileName);
        	// var_dump($ret->createCommand()->getRawSql());die;
			if (Book::find()->where([
				'like',
				'name',
				$bookName
			])->one()) {
				echo '《' . $bookName . '》已重复，略过....' . PHP_EOL;
				continue;
			}


			// 配置
			self::$chapter = 1; // 初始化开始章节计数
			self::$reg = ''; // 初始化正则
			self::$isEnd = false;
			self::$list = self::$regNoise = self::$regBeatifulTitle = self::$regBeatifulContent = []; // 初始化正则
			switch ($fileName) {
				case '别闹，这不科学.txt':
					self::$reg = '/((第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/';
					break;
				case '亲爱的阿基米德.txt':
					self::$reg = '/\d+\、.*(?=[\s\S])/';
					break;
				case '复贵盈门.txt':
					self::$reg = '/((\【?第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/';
					self::$regBeatifulContent = [
						// 美化缩进
						[
							'reg' => '/(?<=\n)/u',
							'replace' => "\t",
						],
					];
					break;
				case '嫡女心计.txt':
					self::$reg = '/(((?<!章节名：)第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/';
					self::$regNoise = [
						'/(((章节名：)第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/',
						'/更新时间:[1-9]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s+(20|21|22|23|[0-1]\d):[0-5]\d:[0-5]\d 本章字数\:\d+/',
					];
					break;
				case '心理支配者.txt':
					self::$reg = '/((VIP([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/';
					self::$regBeatifulTitle = [
						// 去掉
						[
							'reg' => '/VIP章节 \d+/u',
							'replace' => "",
						],
					];
					break;
				case '斗满堂.txt':
					self::$reg = '/((第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/';
					self::$regBeatifulContent = [
						// 美化缩进
						[
							'reg' => '/(?<=\n)/u',
							'replace' => "\t",
						],
					];
					break;
				case '清宫-宛妃传.txt':
					self::$reg = '/(((?<!.)第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)[章|卷]).*(?=[\s\S]))/';
					self::$regBeatifulContent = [
						// 美化缩进
						[
							'reg' => '/(?<=\n)/u',
							'replace' => "\t",
						],
					];
					break;
				case '督主.txt':
					self::$reg = '/\、(\d+.*(?=[\s\S]))/';
					self::$regBeatifulTitle = [
						// 去掉
						[
							'reg' => '/\、/u',
							'replace' => "",
						],
					];
					self::$regBeatifulContent = [
						// 美化缩进
						[
							'reg' => '/(?<=\n)/u',
							'replace' => "\t",
						],
					];
					break;
				case '穿越侯门女之将门妇.txt':
					self::$reg = '/((第([\d]*|[一|二|三|四|五|六|七|八|九|十|百|千|万]+)章).*(?=[\s\S]))/';
					break;
				default:
					# code...
					break;
			}

			// 拆分小说
			$list = $this->_getContent(0, $handle, '');
			// var_dump($list);die;
	        $transaction = \Yii::$app->db->beginTransaction();
	        try {
	        	// 书籍
	 			$bookModel = new Book;
        		$bookModel->name = $bookName;
        		$bookModel->state = 1;
        		$bookModel->sort = 0;
        		$bookModel->type = $sex;
        		$bookModel->created_at = time();
        		$bookModel->updated_at = time();
        		$bookModel->chapter_number = count($list);
	            if (!$bookModel->save(false)) throw new \Exception(current(array_values($bookModel->getFirstErrors())));
	            $book_id = $bookModel->id;
	            // 章节列表
	            foreach ($list as $book) {
	            	$chapter = new BookChapter;
        			$chapter->book_id = $book_id;
        			$chapter->name = trim(str_replace('\n\r', '', $book['title']));
        			$chapter->number = $book['chapter'] ?? 999;
	        		$chapter->created_at = time();
	        		$chapter->updated_at = time();
			        // 保存内容
			        $log_path = './protected/book_' . $book_id . '/';
			        if (!file_exists($log_path)) {
			            $ret = mkdir($log_path, 0777, true);
			        }
			        // 保存为txt文件
			        file_put_contents($log_path . $book['chapter'] . '.txt', $book['content']);

	            	if (!$chapter->save(false)) throw new \Exception(current(array_values($chapter->getFirstErrors())));
	            }
	            $transaction->commit();
	        } catch (\Exception $e) {
	            $transaction->rollBack(); 
	            echo $e->getMessage() . PHP_EOL;
	        }
		}

	}


	public function _getContent($start, $handle = null, $content = '')
	{
		$chapterStart = ftell($handle); // 行开头指针

		if (self::$isEnd) fseek($handle, $start); // 如果是上一章已结束的，把指针指向下一章开头.
		$now = fgets($handle); // 行内容
		preg_match(self::$reg, $now, $ret);
		// 标题
		if (!empty($ret)) { 
			if (!empty(self::$title)) // 标题存在一次
			{
				// 说明是下一章
				self::$isEnd = true;
			}else {
				self::$isEnd = false;
				self::$title = $now; // 存入标题
			}
		}else {
		// 内容
			$content .= $now;
		}
		// 如果章节结束 || 文件末尾
		if (self::$isEnd || feof($handle)) {
			echo self::$chapter . '('.self::$title.')章节(指针开始:'.$start.')结束.end...下一章指针' . $chapterStart . PHP_EOL;

			// 清洗内容噪音 {{{
			foreach (self::$regNoise as $regTmp) {
        		$content = preg_replace($regTmp, "", $content);
			}
			$content = ltrim($content);
			// }}}
			// 美化标题 {{{
			foreach (self::$regBeatifulTitle as $regData) {
        		self::$title = preg_replace($regData['reg'], $regData['replace'], self::$title);
			}
			// }}}
			// 美化内容 {{{
			foreach (self::$regBeatifulContent as $regData) {
        		$content = preg_replace($regData['reg'], $regData['replace'], $content);
			}
			// }}}
			
			// 去掉变态空格 首+尾
			self::$title = preg_replace("/^[\s\v".chr(227).chr(128)."]+/","", self::$title);
			self::$title = preg_replace("/[\s\v".chr(227).chr(128)."]+$/","", self::$title);

			// 存入
			self::$list[] = [
				'chapter' => self::$chapter,
				'title' => self::$title,// 本章标题
				'content' => $content,// 本章内容
			];
			self::$title = $content = ''; // 初始化（下一章用）
			self::$chapter++; // 章节计数
			// 文件结束 返回
			if (feof($handle)) return self::$list;
		}

		// 递归
		return $this->_getContent($chapterStart, $handle, $content);
	}
}