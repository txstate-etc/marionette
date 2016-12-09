<?php
class bb_text extends widget {
	private $tag;
	protected function create($parent, $settings) {
		$this->tag = $settings['tag'];
	}
	public function output() {
		return '<'.$this->tag.'>'.widget::output().'</'.$this->tag.'>';
	}
}

class bb_link extends widget {
	private $lnk;
	protected function create($parent, $settings) {
		$attr = $settings['attr'];
		if (!$attr['url']) $attr['url'] = $attr['href'];
		$linfo = link::parse_href($attr['url'], FALSE);
		$href = $linfo['file'];
		$vars = $linfo['vars'];
		$hash = $linfo['hash'];
		$lnk = new link($parent, $href, '', $vars, $attr['class']);
		if ($hash) $lnk->sethash($hash);
		if ($attr['title']) $lnk->settitle($attr['title']);
		$this->lnk = $lnk;
		return $lnk;
	}
	public function addText($text, $class = '', $strict = FALSE) {
		if (!$this->lnk->gethref()) {
			$linfo = link::parse_href($text, FALSE);
			$href = $linfo['file'];
			$vars = $linfo['vars'];
			$hash = $linfo['hash'];
			$this->lnk->sethref($href, $vars, $hash);
		}
		parent::addText($text, $class, $strict);
	}
}

class bb_img extends widget {
	private $img;
	protected function create($parent, $settings) {
		$attr = $settings['attr'];
		if (!$attr['img']) $attr['img'] = $attr['src'];
		if (!$attr['w']) $attr['w'] = $attr['width'];
		if (!$attr['h']) $attr['h'] = $attr['height'];
		
		if ($attr['float']) {
			static $once = TRUE;
			if ($once) {
				doc::getdoc()->addCSS('.bbcodeblock .bbcode_float_left { float: left; }');
				doc::getdoc()->addCSS('.bbcodeblock .bbcode_float_right { float: right; }');
				$once = FALSE;
			}
			if (strtolower($attr['float']) == 'right') $attr['class'] = 'bbcode_float_right';
			else $attr['class'] = 'bbcode_float_left';
		}
		
		if ($attr['caption']) {
			$outer = new div($parent, '', $attr['class']);
		} else {
			$outer = $parent;
		}
		
		$img = new image($outer, '', $attr['w'], $attr['h'], $attr['alt'], $attr['class']);

		if ($attr['caption']) {
			$inner = new div($parent, '', 'bbcode_caption');
			$inner->addText($attr['caption']);
		}
		
		$linfo = link::parse_href($attr['img'], FALSE);
		$href = $linfo['file'];
		$vars = $linfo['vars'];
		$img->setsrc($href, $vars);

		if ($attr['title']) $img->settitle($attr['title']);
		$this->img = $img;
		return $parent;
	}
	public function addText($text, $class = '') {
		if (!$this->img->getsrc()) {
			$linfo = link::parse_href(trim($text), FALSE);
			$href = $linfo['file'];
			$vars = $linfo['vars'];
			$this->img->setsrc($href, $vars);
		} elseif (!$this->img->getalt()) {
			$this->img->setalt($text);
		}
	}
}

class bb_object extends widget {
	private $obj;
	protected function create($parent, $settings) {
		$attr = $settings['attr'];
		if (!$attr['object']) $attr['object'] = $attr['src'];
		if (!$attr['w']) $attr['w'] = $attr['width'];
		if (!$attr['h']) $attr['h'] = $attr['height'];
		$specialkeys = array(
			'w' => TRUE,
			'h' => TRUE,
			'width' => TRUE,
			'height' => TRUE,
			'object' => TRUE,
			'src' => TRUE,
			'class' => TRUE,
		);
		$obj = new mediaobject($parent, '', $attr['w'], $attr['h'], array_diff_assoc($attr, $specialkeys), $attr['class']);

		$linfo = link::parse_href($attr['object'], FALSE);
		$href = $linfo['file'];
		$vars = $linfo['vars'];
		$obj->setsrc($href, $vars);
		
		$this->obj = $obj;
	}
	public function addText($text, $class = '') {
		if (!$this->obj->getsrc()) {
			$linfo = link::parse_href($text, FALSE);
			$href = $linfo['file'];
			$vars = $linfo['vars'];
			$this->obj->setsrc($href, $vars);
		}
	}
}

class bb_code extends widget {
	protected function create($parent, $settings) {
		$attr = $settings['attr'];
		$code = new codeblock($parent, $attr['class']);
		return $code;
	}
}

class bb_quote extends widget {
	protected function create($parent, $settings) {
		$attr = $settings['attr'];
		$quote = new blockquote($parent, $attr['class']);
		if ($attr['quote']) {
			$div = new div($quote, '', 'bbcode_quoted');
			$div->addStrict('Quoting: '.$attr['quote']);
		}
		return $quote;
	}
}

class bb_span extends widget {
	protected function create($parent, $settings) {
		$tag = $settings['tag'];
		$attr = $settings['attr'];
		$specialclass = generatestring(6,'alpha');
		if ($tag == 'color' && $attr['color']) {
			$attr['class'] = $specialclass;
			doc::getdoc()->addCSS('.bbcodeblock .'.$specialclass.' { color: '.$attr['color'].'; }');
		}
		elseif ($tag == 'size') {
			if (!$attr['size']) $attr['size'] = 1;
			doc::getdoc()->addCSS('.bbcodeblock .'.$specialclass.' { size: '.$attr['size'].'em; }');
			$attr['class'] = $specialclass;
		} elseif ($tag == 'small') {
			$attr['class'] = 'bbcode_small';
		} elseif ($tag == 'big') {
			$attr['class'] = 'bbcode_big';
		}
		$span = new span($parent, '', $attr['class']);
		return $span;
	}
}
class bb_divider extends widget {
	protected function create($parent, $settings) {
		$attr = $settings['attr'];
		if (!$attr['w']) $attr['w'] = $attr['width'];
		new divider($parent, $attr['w'], $attr['class']);
		return $parent;
	}
}
class bb_clear extends widget {
	protected function create($parent, $settings) {
		new clear($parent);
		return $parent;
	}
}

?>
