<?php

namespace core\sdk\markdown;

class Parser
{
    public $_white = 'kbd|b|i|strong|em|sup|sub|br|code|del|a|hr|small';
    public $_block = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|form|fieldset|iframe|hr|legend|article|section|nav|aside|hgroup|header|footer|figcaption|svg|script|noscript';
    public $_specialWhiteList = array(
        'table' => 'table|tbody|thead|tfoot|tr|td|th'
    );
    public $_footnotes;
    public $_html = false;
    public $_line = false;
    public $blockParsers = array(
        array('code', 10),
        array('shtml', 20),
        array('pre', 30),
        array('ahtml', 40),
        array('shr', 50),
        array('list', 60),
        array('math', 70),
        array('html', 80),
        array('footnote', 90),
        array('definition', 100),
        array('quote', 110),
        array('table', 120),
        array('sh', 130),
        array('mh', 140),
        array('dhr', 150),
        array('default', 9999)
    );
    private $_blocks;
    private $_current;
    private $_pos;
    public $_definitions;
    private $_hooks = array();
    private $_holders;
    private $_uniqid;
    private $_id;
    private $_prsrs = array();

    public function parse($text)
    {
        $this->_footnotes = array();
        $this->_definitions = array();
        $this->_holders = array();
        $this->_uniqid = md5(uniqid());
        $this->_id = 0;

        usort($this->blockParsers, function ($a, $b) {
            return $a[1] < $b[1] ? -1 : 1;
        });

        foreach ($this->blockParsers as $prsr) {
            list ($name) = $prsr;

            if (isset($prsr[2])) {
                $this->_prsrs[$name] = $prsr[2];
            } else {
                $this->_prsrs[$name] = array($this, 'prsBlock' . ucfirst($name));
            }
        }

        $text = $this->initText($text);
        $html = $this->prs($text);
        $html = $this->makeFootnotes($html);
        $html = $this->optimizeLines($html);

        return $this->call('parse', $html);
    }

    public function enableHtml($html = true)
    {
        $this->_html = $html;
    }

    public function enableLine($line = true)
    {
        $this->_line = $line;
    }

    public function hook($type, $callback)
    {
        $this->_hooks[$type][] = $callback;
    }

    public function makeHolder($str)
    {
        $key = "\r" . $this->_uniqid . $this->_id . "\r";
        $this->_id++;
        $this->_holders[$key] = $str;

        return $key;
    }

    private function initText($text)
    {
        $text = str_replace(array("\t", "\r"), array('    ', ''), $text);
        return $text;
    }

    private function makeFootnotes($html)
    {
        if (count($this->_footnotes) > 0) {
            $html .= '<div class="footnotes"><hr><ol>';
            $index = 1;

            while ($val = array_shift($this->_footnotes)) {
                if (is_string($val)) {
                    $val .= " <a href=\"#fnref-{$index}\" class=\"footnote-backref\">&#8617;</a>";
                } else {
                    $val[count($val) - 1] .= " <a href=\"#fnref-{$index}\" class=\"footnote-backref\">&#8617;</a>";
                    $val = count($val) > 1 ? $this->prs(implode("\n", $val)) : $this->prsInline($val[0]);
                }

                $html .= "<li id=\"fn-{$index}\">{$val}</li>";
                $index++;
            }

            $html .= '</ol></div>';
        }

        return $html;
    }

    private function prs($text, $inline = false, $offset = 0)
    {
        $blocks = $this->prsBlock($text, $lines);
        $html = '';


        if ($inline && count($blocks) == 1 && $blocks[0][0] == 'normal') {
            $blocks[0][3] = true;
        }

        foreach ($blocks as $block) {
            list ($type, $start, $end, $value) = $block;
            $extract = array_slice($lines, $start, $end - $start + 1);
            $method = 'prs' . ucfirst($type);

            $extract = $this->call('before' . ucfirst($method), $extract, $value);
            $result = $this->{$method}($extract, $value, $start + $offset, $end + $offset);
            $result = $this->call('after' . ucfirst($method), $result, $value);

            $html .= $result;
        }

        return $html;
    }

    private function releaseHolder($text, $clearHolders = true)
    {
        $deep = 0;
        while (strpos($text, "\r") !== false && $deep < 10) {
            $text = str_replace(array_keys($this->_holders), array_values($this->_holders), $text);
            $deep++;
        }

        if ($clearHolders) {
            $this->_holders = array();
        }

        return $text;
    }

    public function markLine($start, $end = -1)
    {
        if ($this->_line) {
            $end = $end < 0 ? $start : $end;
            return '<span class="line" data-start="' . $start
                . '" data-end="' . $end . '" data-id="' . $this->_uniqid . '"></span>';
        }

        return '';
    }

    public function markLines(array $lines, $start)
    {
        $i = -1;
        $self = $this;

        return $this->_line ? array_map(function ($line) use ($self, $start, &$i) {
            $i++;
            return $self->markLine($start + $i) . $line;
        }, $lines) : $lines;
    }

    public function optimizeLines($html)
    {
        $last = 0;

        return $this->_line ?
            preg_replace_callback("/class=\"line\" data\-start=\"([0-9]+)\" data\-end=\"([0-9]+)\" (data\-id=\"{$this->_uniqid}\")/",
                function ($matches) use (&$last) {
                    if ($matches[1] != $last) {
                        $replace = 'class="line" data-start="' . $last . '" data-start-original="' . $matches[1] . '" data-end="' . $matches[2] . '" ' . $matches[3];
                    } else {
                        $replace = $matches[0];
                    }

                    $last = $matches[2] + 1;
                    return $replace;
                }, $html) : $html;
    }

    public function call($type, $value)
    {
        if (empty($this->_hooks[$type])) {
            return $value;
        }

        $args = func_get_args();
        $args = array_slice($args, 1);

        foreach ($this->_hooks[$type] as $callback) {
            $value = call_user_func_array($callback, $args);
            $args[0] = $value;
        }

        return $value;
    }

    public function prsInline($text, $whiteList = '', $clearHolders = true, $enableAutoLink = true)
    {
        $self = $this;
        $text = $this->call('beforeParseInline', $text);


        $text = preg_replace_callback(
            "/(^|[^\\\])(`+)(.+?)\\2/",
            function ($matches) use ($self) {
                return $matches[1] . $self->makeHolder(
                        '<code>' . htmlspecialchars($matches[3]) . '</code>'
                    );
            },
            $text
        );


        $text = preg_replace_callback(
            "/(^|[^\\\])(\\$+)(.+?)\\2/",
            function ($matches) use ($self) {
                return $matches[1] . $self->makeHolder(
                        $matches[2] . htmlspecialchars($matches[3]) . $matches[2]
                    );
            },
            $text
        );


        $text = preg_replace_callback(
            "/\\\(.)/u",
            function ($matches) use ($self) {
                $prefix = preg_match("/^[-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]$/", $matches[1]) ? '' : '\\';
                $escaped = htmlspecialchars($matches[1]);
                $escaped = str_replace('$', '&dollar;', $escaped);
                return $self->makeHolder($prefix . $escaped);
            },
            $text
        );


        $text = preg_replace_callback(
            "/<(https?:\/\/.+)>/i",
            function ($matches) use ($self) {
                $url = $self->cleanUrl($matches[1]);
                $link = $self->call('prsLink', $matches[1]);

                return $self->makeHolder(
                    "<a href=\"{$url}\">{$link}</a>"
                );
            },
            $text
        );


        $text = preg_replace_callback(
            "/<(\/?)([a-z0-9-]+)(\s+[^>]*)?>/i",
            function ($matches) use ($self, $whiteList) {
                if ($self->_html || false !== stripos(
                        '|' . $self->_white . '|' . $whiteList . '|', '|' . $matches[2] . '|'
                    )) {
                    return $self->makeHolder($matches[0]);
                } else {
                    return $self->makeHolder(htmlspecialchars($matches[0]));
                }
            },
            $text
        );

        if ($this->_html) {
            $text = preg_replace_callback("/<!\-\-(.*?)\-\->/", function ($matches) use ($self) {
                return $self->makeHolder($matches[0]);
            }, $text);
        }

        $text = str_replace(array('<', '>'), array('&lt;', '&gt;'), $text);


        $text = preg_replace_callback(
            "/\[\^((?:[^\]]|\\\\\]|\\\\\[)+?)\]/",
            function ($matches) use ($self) {
                $id = array_search($matches[1], $self->_footnotes);

                if (false === $id) {
                    $id = count($self->_footnotes) + 1;
                    $self->_footnotes[$id] = $self->prsInline($matches[1], '', false);
                }

                return $self->makeHolder(
                    "<sup id=\"fnref-{$id}\"><a href=\"#fn-{$id}\" class=\"footnote-ref\">{$id}</a></sup>"
                );
            },
            $text
        );


        $text = preg_replace_callback(
            "/!\[((?:[^\]]|\\\\\]|\\\\\[)*?)\]\(((?:[^\)]|\\\\\)|\\\\\()+?)\)/",
            function ($matches) use ($self) {
                $escaped = htmlspecialchars($self->escapeBracket($matches[1]));
                $url = $self->escapeBracket($matches[2]);
                $url = $self->cleanUrl($url);
                return $self->makeHolder(
                    "<img src=\"{$url}\" alt=\"{$escaped}\" title=\"{$escaped}\">"
                );
            },
            $text
        );

        $text = preg_replace_callback(
            "/!\[((?:[^\]]|\\\\\]|\\\\\[)*?)\]\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]/",
            function ($matches) use ($self) {
                $escaped = htmlspecialchars($self->escapeBracket($matches[1]));

                $result = isset($self->_definitions[$matches[2]]) ?
                    "<img src=\"{$self->_definitions[$matches[2]]}\" alt=\"{$escaped}\" title=\"{$escaped}\">"
                    : $escaped;

                return $self->makeHolder($result);
            },
            $text
        );


        $text = preg_replace_callback(
            "/\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]\(((?:[^\)]|\\\\\)|\\\\\()+?)\)/",
            function ($matches) use ($self) {
                $escaped = $self->prsInline(
                    $self->escapeBracket($matches[1]), '', false, false
                );
                $url = $self->escapeBracket($matches[2]);
                $url = $self->cleanUrl($url);
                return $self->makeHolder("<a href=\"{$url}\">{$escaped}</a>");
            },
            $text
        );

        $text = preg_replace_callback(
            "/\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]/",
            function ($matches) use ($self) {
                $escaped = $self->prsInline(
                    $self->escapeBracket($matches[1]), '', false
                );
                $result = isset($self->_definitions[$matches[2]]) ?
                    "<a href=\"{$self->_definitions[$matches[2]]}\">{$escaped}</a>"
                    : $escaped;

                return $self->makeHolder($result);
            },
            $text
        );


        $text = $this->prsInlineCallback($text);
        $text = preg_replace(
            "/<([_a-z0-9-\.\+]+@[^@]+\.[a-z]{2,})>/i",
            "<a href=\"mailto:\\1\">\\1</a>",
            $text
        );


        if ($enableAutoLink) {
            $text = preg_replace_callback(
                "/(^|[^\"])((https?):[\p{L}_0-9-\.\/%#!@\?\+=~\|\,&\(\)]+)($|[^\"])/iu",
                function ($matches) use ($self) {
                    $link = $self->call('prsLink', $matches[2]);
                    return "{$matches[1]}<a href=\"{$matches[2]}\">{$link}</a>{$matches[4]}";
                },
                $text
            );
        }

        $text = $this->call('afterParseInlineBeforeRelease', $text);
        $text = $this->releaseHolder($text, $clearHolders);

        $text = $this->call('afterParseInline', $text);

        return $text;
    }

    public function prsInlineCallback($text)
    {
        $self = $this;

        $text = preg_replace_callback(
            "/(\*{3})(.+?)\\1/",
            function ($matches) use ($self) {
                return '<strong><em>' .
                    $self->prsInlineCallback($matches[2]) .
                    '</em></strong>';
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\*{2})(.+?)\\1/",
            function ($matches) use ($self) {
                return '<strong>' .
                    $self->prsInlineCallback($matches[2]) .
                    '</strong>';
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\*)(.+?)\\1/",
            function ($matches) use ($self) {
                return '<em>' .
                    $self->prsInlineCallback($matches[2]) .
                    '</em>';
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\s+|^)(_{3})(.+?)\\2(\s+|$)/",
            function ($matches) use ($self) {
                return $matches[1] . '<strong><em>' .
                    $self->prsInlineCallback($matches[3]) .
                    '</em></strong>' . $matches[4];
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\s+|^)(_{2})(.+?)\\2(\s+|$)/",
            function ($matches) use ($self) {
                return $matches[1] . '<strong>' .
                    $self->prsInlineCallback($matches[3]) .
                    '</strong>' . $matches[4];
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\s+|^)(_)(.+?)\\2(\s+|$)/",
            function ($matches) use ($self) {
                return $matches[1] . '<em>' .
                    $self->prsInlineCallback($matches[3]) .
                    '</em>' . $matches[4];
            },
            $text
        );

        $text = preg_replace_callback(
            "/(~{2})(.+?)\\1/",
            function ($matches) use ($self) {
                return '<del>' .
                    $self->prsInlineCallback($matches[2]) .
                    '</del>';
            },
            $text
        );

        return $text;
    }

    private function prsBlock($text, &$lines)
    {
        $lines = explode("\n", $text);
        $this->_blocks = array();
        $this->_current = 'normal';
        $this->_pos = -1;

        $state = array(
            'special' => implode("|", array_keys($this->_specialWhiteList)),
            'empty' => 0,
            'html' => false
        );


        foreach ($lines as $key => $line) {
            $block = $this->get_block();
            $args = array($block, $key, $line, &$state, $lines);

            if ($this->_current != 'normal') {
                $pass = call_user_func_array($this->_prsrs[$this->_current], $args);

                if (!$pass) {
                    continue;
                }
            }

            foreach ($this->_prsrs as $name => $prsr) {
                if ($name != $this->_current) {
                    $pass = call_user_func_array($prsr, $args);

                    if (!$pass) {
                        break;
                    }
                }
            }
        }

        return $this->optimizeBlocks($this->_blocks, $lines);
    }

    private function prsBlockList($block, $key, $line, &$state)
    {
        if ($this->is_block('list') && !preg_match("/^\s*\[((?:[^\]]|\\]|\\[)+?)\]:\s*(.+)$/", $line)) {
            if ($state['empty'] <= 1
                && preg_match("/^(\s+)/", $line, $matches)
                && strlen($matches[1]) > $block[3]) {

                $state['empty'] = 0;
                $this->set_block($key);
                return false;
            } else if (preg_match("/^(\s*)$/", $line) && $state['empty'] == 0) {
                $state['empty']++;
                $this->set_block($key);
                return false;
            }
        }

        if (preg_match("/^(\s*)((?:[0-9]+\.)|\-|\+|\*)\s+/i", $line, $matches)) {
            $space = strlen($matches[1]);
            $state['empty'] = 0;


            if ($this->is_block('list')) {
                $this->set_block($key, $space);
            } else {
                $this->start_block('list', $key, $space);
            }

            return false;
        }

        return true;
    }

    private function prsBlockCode($block, $key, $line)
    {
        if (preg_match("/^(\s*)(~{3,}|`{3,})([^`~]*)$/i", $line, $matches)) {
            if ($this->is_block('code')) {
                $isAfterList = $block[3][2];

                if ($isAfterList) {
                    $this->combine_block()
                        ->set_block($key);
                } else {
                    $this->set_block($key)
                        ->end_block();
                }
            } else {
                $isAfterList = false;

                if ($this->is_block('list')) {
                    $space = $block[3];

                    $isAfterList = ($space > 0 && strlen($matches[1]) >= $space)
                        || strlen($matches[1]) > $space;
                }

                $this->start_block('code', $key, array(
                    $matches[1], $matches[3], $isAfterList
                ));
            }

            return false;
        } else if ($this->is_block('code')) {
            $this->set_block($key);
            return false;
        }

        return true;
    }

    private function prsBlockShtml($block, $key, $line, &$state)
    {
        if ($this->_html) {
            if (preg_match("/^(\s*)!!!(\s*)$/", $line, $matches)) {
                if ($this->is_block('shtml')) {
                    $this->set_block($key)->end_block();
                } else {
                    $this->start_block('shtml', $key);
                }

                return false;
            } else if ($this->is_block('shtml')) {
                $this->set_block($key);
                return false;
            }
        }

        return true;
    }

    private function prsBlockAhtml($block, $key, $line, &$state)
    {
        if ($this->_html) {
            if (preg_match("/^\s*<({$this->_block})(\s+[^>]*)?>/i", $line, $matches)) {
                if ($this->is_block('ahtml')) {
                    $this->set_block($key);
                    return false;
                } else if (empty($matches[2]) || $matches[2] != '/') {
                    $this->start_block('ahtml', $key);
                    preg_match_all("/<({$this->_block})(\s+[^>]*)?>/i", $line, $allMatches);
                    $lastMatch = $allMatches[1][count($allMatches[0]) - 1];

                    if (strpos($line, "</{$lastMatch}>") !== false) {
                        $this->end_block();
                    } else {
                        $state['html'] = $lastMatch;
                    }
                    return false;
                }
            } else if (!!$state['html'] && strpos($line, "</{$state['html']}>") !== false) {
                $this->set_block($key)->end_block();
                $state['html'] = false;
                return false;
            } else if ($this->is_block('ahtml')) {
                $this->set_block($key);
                return false;
            } else if (preg_match("/^\s*<!\-\-(.*?)\-\->\s*$/", $line, $matches)) {
                $this->start_block('ahtml', $key)->end_block();
                return false;
            }
        }

        return true;
    }

    private function prsBlockMath($block, $key, $line)
    {
        if (preg_match("/^(\s*)\\$\\$(\s*)$/", $line, $matches)) {
            if ($this->is_block('math')) {
                $this->set_block($key)->end_block();
            } else {
                $this->start_block('math', $key);
            }

            return false;
        } else if ($this->is_block('math')) {
            $this->set_block($key);
            return false;
        }

        return true;
    }

    private function prsBlockPre($block, $key, $line, &$state)
    {
        if (preg_match("/^ {4}/", $line)) {
            if ($this->is_block('pre')) {
                $this->set_block($key);
            } else {
                $this->start_block('pre', $key);
            }

            return false;
        } else if ($this->is_block('pre') && preg_match("/^\s*$/", $line)) {
            $this->set_block($key);
            return false;
        }

        return true;
    }

    private function prsBlockHtml($block, $key, $line, &$state)
    {
        if (preg_match("/^\s*<({$state['special']})(\s+[^>]*)?>/i", $line, $matches)) {
            $tag = strtolower($matches[1]);
            if (!$this->is_block('html', $tag) && !$this->is_block('pre')) {
                $this->start_block('html', $key, $tag);
            }

            return false;
        } else if (preg_match("/<\/({$state['special']})>\s*$/i", $line, $matches)) {
            $tag = strtolower($matches[1]);

            if ($this->is_block('html', $tag)) {
                $this->set_block($key)
                    ->end_block();
            }

            return false;
        } else if ($this->is_block('html')) {
            $this->set_block($key);
            return false;
        }

        return true;
    }

    private function prsBlockFootnote($block, $key, $line)
    {
        if (preg_match("/^\[\^((?:[^\]]|\\]|\\[)+?)\]:/", $line, $matches)) {
            $space = strlen($matches[0]) - 1;
            $this->start_block('footnote', $key, array(
                $space, $matches[1]
            ));

            return false;
        }

        return true;
    }

    private function prsBlockDefinition($block, $key, $line)
    {
        if (preg_match("/^\s*\[((?:[^\]]|\\]|\\[)+?)\]:\s*(.+)$/", $line, $matches)) {
            $this->_definitions[$matches[1]] = $this->cleanUrl($matches[2]);
            $this->start_block('definition', $key)
                ->end_block();

            return false;
        }

        return true;
    }

    private function prsBlockQuote($block, $key, $line)
    {
        if (preg_match("/^(\s*)>/", $line, $matches)) {
            if ($this->is_block('list') && strlen($matches[1]) > 0) {
                $this->set_block($key);
            } else if ($this->is_block('quote')) {
                $this->set_block($key);
            } else {
                $this->start_block('quote', $key);
            }

            return false;
        }

        return true;
    }

    private function prsBlockTable($block, $key, $line, &$state, $lines)
    {
        if (preg_match("/^((?:(?:(?:\||\+)(?:[ :]*\-+[ :]*)(?:\||\+))|(?:(?:[ :]*\-+[ :]*)(?:\||\+)(?:[ :]*\-+[ :]*))|(?:(?:[ :]*\-+[ :]*)(?:\||\+))|(?:(?:\||\+)(?:[ :]*\-+[ :]*)))+)$/", $line, $matches)) {
            if ($this->is_block('table')) {
                $block[3][0][] = $block[3][2];
                $block[3][2]++;
                $this->set_block($key, $block[3]);
            } else {
                $head = 0;

                if (empty($block) ||
                    $block[0] != 'normal' ||
                    preg_match("/^\s*$/", $lines[$block[2]])) {
                    $this->start_block('table', $key);
                } else {
                    $head = 1;
                    $this->back_block(1, 'table');
                }

                if ($matches[1][0] == '|') {
                    $matches[1] = substr($matches[1], 1);

                    if ($matches[1][strlen($matches[1]) - 1] == '|') {
                        $matches[1] = substr($matches[1], 0, -1);
                    }
                }

                $rows = preg_split("/(\+|\|)/", $matches[1]);
                $aligns = array();
                foreach ($rows as $row) {
                    $align = 'none';

                    if (preg_match("/^\s*(:?)\-+(:?)\s*$/", $row, $matches)) {
                        if (!empty($matches[1]) && !empty($matches[2])) {
                            $align = 'center';
                        } else if (!empty($matches[1])) {
                            $align = 'left';
                        } else if (!empty($matches[2])) {
                            $align = 'right';
                        }
                    }

                    $aligns[] = $align;
                }

                $this->set_block($key, array(array($head), $aligns, $head + 1));
            }

            return false;
        }

        return true;
    }

    private function prsBlockSh($block, $key, $line)
    {
        if (preg_match("/^(#+)(.*)$/", $line, $matches)) {
            $num = min(strlen($matches[1]), 6);
            $this->start_block('sh', $key, $num)
                ->end_block();

            return false;
        }

        return true;
    }

    private function prsBlockMh($block, $key, $line, &$state, $lines)
    {
        if (preg_match("/^\s*((=|-){2,})\s*$/", $line, $matches)
            && ($block && $block[0] == "normal" && !preg_match("/^\s*$/", $lines[$block[2]]))) {
            if ($this->is_block('normal')) {
                $this->back_block(1, 'mh', $matches[1][0] == '=' ? 1 : 2)
                    ->set_block($key)
                    ->end_block();
            } else {
                $this->start_block('normal', $key);
            }

            return false;
        }

        return true;
    }

    private function prsBlockShr($block, $key, $line)
    {
        if (preg_match("/^(\* *){3,}\s*$/", $line)) {
            $this->start_block('hr', $key)
                ->end_block();

            return false;
        }

        return true;
    }

    private function prsBlockDhr($block, $key, $line)
    {
        if (preg_match("/^(- *){3,}\s*$/", $line)) {
            $this->start_block('hr', $key)
                ->end_block();

            return false;
        }

        return true;
    }

    private function prsBlockDefault($block, $key, $line, &$state)
    {
        if ($this->is_block('footnote')) {
            preg_match("/^(\s*)/", $line, $matches);
            if (strlen($matches[1]) >= $block[3][0]) {
                $this->set_block($key);
            } else {
                $this->start_block('normal', $key);
            }
        } else if ($this->is_block('table')) {
            if (false !== strpos($line, '|')) {
                $block[3][2]++;
                $this->set_block($key, $block[3]);
            } else {
                $this->start_block('normal', $key);
            }
        } else if ($this->is_block('quote')) {
            if (!preg_match("/^(\s*)$/", $line)) {
                $this->set_block($key);
            } else {
                $this->start_block('normal', $key);
            }
        } else {
            if (empty($block) || $block[0] != 'normal') {
                $this->start_block('normal', $key);
            } else {
                $this->set_block($key);
            }
        }

        return true;
    }

    private function optimizeBlocks(array $blocks, array $lines)
    {
        $blocks = $this->call('beforeOptimizeBlocks', $blocks, $lines);

        $key = 0;
        while (isset($blocks[$key])) {
            $moved = false;

            $block = &$blocks[$key];
            $prevBlock = isset($blocks[$key - 1]) ? $blocks[$key - 1] : NULL;
            $nextBlock = isset($blocks[$key + 1]) ? $blocks[$key + 1] : NULL;

            list ($type, $from, $to) = $block;

            if ('pre' == $type) {
                $isEmpty = array_reduce(
                    array_slice($lines, $block[1], $block[2] - $block[1] + 1),
                    function ($result, $line) {
                        return preg_match("/^\s*$/", $line) && $result;
                    },
                    true
                );

                if ($isEmpty) {
                    $block[0] = $type = 'normal';
                }
            }

            if ('normal' == $type) {

                $types = array('list', 'quote');

                if ($from == $to && preg_match("/^\s*$/", $lines[$from])
                    && !empty($prevBlock) && !empty($nextBlock)) {
                    if ($prevBlock[0] == $nextBlock[0] && in_array($prevBlock[0], $types)) {

                        $blocks[$key - 1] = array(
                            $prevBlock[0], $prevBlock[1], $nextBlock[2], NULL
                        );
                        array_splice($blocks, $key, 2);


                        $moved = true;
                    }
                }
            }

            if (!$moved) {
                $key++;
            }
        }

        return $this->call('afterOptimizeBlocks', $blocks, $lines);
    }

    private function prsCode(array $lines, array $parts, $start)
    {
        list ($blank, $lang) = $parts;
        $lang = trim($lang);
        $count = strlen($blank);

        if (!preg_match("/^[_a-z0-9-\+\#\:\.]+$/i", $lang)) {
            $lang = NULL;
        } else {
            $parts = explode(':', $lang);
            if (count($parts) > 1) {
                list ($lang, $rel) = $parts;
                $lang = trim($lang);
                $rel = trim($rel);
            }
        }

        $isEmpty = true;

        $lines = array_map(function ($line) use ($count, &$isEmpty) {
            $line = preg_replace("/^[ ]{{$count}}/", '', $line);
            if ($isEmpty && !preg_match("/^\s*$/", $line)) {
                $isEmpty = false;
            }

            return htmlspecialchars($line);
        }, array_slice($lines, 1, -1));
        $str = implode("\n", $this->markLines($lines, $start + 1));

        return $isEmpty ? '' :
            '<pre><code' . (!empty($lang) ? " class=\"{$lang}\"" : '')
            . (!empty($rel) ? " rel=\"{$rel}\"" : '') . '>'
            . $str . '</code></pre>';
    }

    private function prsPre(array $lines, $value, $start)
    {
        foreach ($lines as &$line) {
            $line = htmlspecialchars(substr($line, 4));
        }

        $str = implode("\n", $this->markLines($lines, $start));
        return preg_match("/^\s*$/", $str) ? '' : '<pre><code>' . $str . '</code></pre>';
    }

    private function prsAhtml(array $lines, $value, $start)
    {
        return trim(implode("\n", $this->markLines($lines, $start)));
    }

    private function prsShtml(array $lines, $value, $start)
    {
        return trim(implode("\n", $this->markLines(array_slice($lines, 1, -1), $start + 1)));
    }

    private function prsMath(array $lines, $value, $start, $end)
    {
        return '<p>' . $this->markLine($start, $end) . htmlspecialchars(implode("\n", $lines)) . '</p>';
    }

    private function prsSh(array $lines, $num, $start, $end)
    {
        $line = $this->markLine($start, $end) . $this->prsInline(trim($lines[0], '# '));
        return preg_match("/^\s*$/", $line) ? '' : "<h{$num}>{$line}</h{$num}>";
    }

    private function prsMh(array $lines, $num, $start, $end)
    {
        return $this->prsSh($lines, $num, $start, $end);
    }

    private function prsQuote(array $lines, $value, $start)
    {
        foreach ($lines as &$line) {
            $line = preg_replace("/^\s*> ?/", '', $line);
        }
        $str = implode("\n", $lines);

        return preg_match("/^\s*$/", $str) ? '' : '<blockquote>' . $this->prs($str, true, $start) . '</blockquote>';
    }

    private function prsList(array $lines, $value, $start)
    {
        $html = '';
        $minSpace = 99999;
        $secondMinSpace = 99999;
        $found = false;
        $secondFound = false;
        $rows = array();


        foreach ($lines as $key => $line) {
            if (preg_match("/^(\s*)((?:[0-9]+\.?)|\-|\+|\*)(\s+)(.*)$/i", $line, $matches)) {
                $space = strlen($matches[1]);
                $type = false !== strpos('+-*', $matches[2]) ? 'ul' : 'ol';
                $minSpace = min($space, $minSpace);
                $found = true;

                if ($space > 0) {
                    $secondMinSpace = min($space, $secondMinSpace);
                    $secondFound = true;
                }

                $rows[] = array($space, $type, $line, $matches[4]);
            } else {
                $rows[] = $line;

                if (preg_match("/^(\s*)/", $line, $matches)) {
                    $space = strlen($matches[1]);

                    if ($space > 0) {
                        $secondMinSpace = min($space, $secondMinSpace);
                        $secondFound = true;
                    }
                }
            }
        }

        $minSpace = $found ? $minSpace : 0;
        $secondMinSpace = $secondFound ? $secondMinSpace : $minSpace;

        $lastType = '';
        $leftLines = array();
        $leftStart = 0;

        foreach ($rows as $key => $row) {
            if (is_array($row)) {
                list ($space, $type, $line, $text) = $row;

                if ($space != $minSpace) {
                    $leftLines[] = preg_replace("/^\s{" . $secondMinSpace . "}/", '', $line);
                } else {
                    if (!empty($leftLines)) {
                        $html .= "<li>" . $this->prs(implode("\n", $leftLines), true, $start + $leftStart) . "</li>";
                    }

                    if ($lastType != $type) {
                        if (!empty($lastType)) {
                            $html .= "</{$lastType}>";
                        }

                        $html .= "<{$type}>";
                    }

                    $leftStart = $key;
                    $leftLines = array($text);
                    $lastType = $type;
                }
            } else {
                $leftLines[] = preg_replace("/^\s{" . $secondMinSpace . "}/", '', $row);
            }
        }

        if (!empty($leftLines)) {
            $html .= "<li>" . $this->prs(implode("\n", $leftLines), true, $start + $leftStart) . "</li></{$lastType}>";
        }

        return $html;
    }

    private function prsTable(array $lines, array $value, $start)
    {
        list ($ignores, $aligns) = $value;
        $head = count($ignores) > 0 && array_sum($ignores) > 0;

        $html = '<table>';
        $body = $head ? NULL : true;
        $output = false;

        foreach ($lines as $key => $line) {
            if (in_array($key, $ignores)) {
                if ($head && $output) {
                    $head = false;
                    $body = true;
                }

                continue;
            }

            $line = trim($line);
            $output = true;

            if ($line[0] == '|') {
                $line = substr($line, 1);

                if ($line[strlen($line) - 1] == '|') {
                    $line = substr($line, 0, -1);
                }
            }


            $rows = array_map(function ($row) {
                if (preg_match("/^\s*$/", $row)) {
                    return ' ';
                } else {
                    return trim($row);
                }
            }, explode('|', $line));
            $columns = array();
            $last = -1;

            foreach ($rows as $row) {
                if (strlen($row) > 0) {
                    $last++;
                    $columns[$last] = array(
                        isset($columns[$last]) ? $columns[$last][0] + 1 : 1, $row
                    );
                } else if (isset($columns[$last])) {
                    $columns[$last][0]++;
                } else {
                    $columns[0] = array(1, $row);
                }
            }

            if ($head) {
                $html .= '<thead>';
            } else if ($body) {
                $html .= '<tbody>';
            }

            $html .= '<tr' . ($this->_line ? ' class="line" data-start="'
                    . ($start + $key) . '" data-end="' . ($start + $key)
                    . '" data-id="' . $this->_uniqid . '"' : '') . '>';

            foreach ($columns as $key => $column) {
                list ($num, $text) = $column;
                $tag = $head ? 'th' : 'td';

                $html .= "<{$tag}";
                if ($num > 1) {
                    $html .= " colspan=\"{$num}\"";
                }

                if (isset($aligns[$key]) && $aligns[$key] != 'none') {
                    $html .= " align=\"{$aligns[$key]}\"";
                }

                $html .= '>' . $this->prsInline($text) . "</{$tag}>";
            }

            $html .= '</tr>';

            if ($head) {
                $html .= '</thead>';
            } else if ($body) {
                $body = false;
            }
        }

        if ($body !== NULL) {
            $html .= '</tbody>';
        }

        $html .= '</table>';
        return $html;
    }

    private function prsHr($lines, $value, $start)
    {
        return $this->_line ? '<hr class="line" data-start="' . $start . '" data-end="' . $start . '">' : '<hr>';
    }

    private function prsNormal(array $lines, $inline = false, $start)
    {
        foreach ($lines as $key => &$line) {
            $line = $this->prsInline($line);

            if (!preg_match("/^\s*$/", $line)) {
                $line = $this->markLine($start + $key) . $line;
            }
        }

        $str = trim(implode("\n", $lines));
        $str = preg_replace("/(\n\s*){2,}/", "</p><p>", $str);
        $str = preg_replace("/\n/", "<br>", $str);

        return preg_match("/^\s*$/", $str) ? '' : ($inline ? $str : "<p>{$str}</p>");
    }

    private function prsFootnote(array $lines, array $value)
    {
        list($space, $note) = $value;
        $index = array_search($note, $this->_footnotes);

        if (false !== $index) {
            $lines[0] = preg_replace("/^\[\^((?:[^\]]|\\]|\\[)+?)\]:/", '', $lines[0]);
            $this->_footnotes[$index] = $lines;
        }

        return '';
    }

    private function prsDefinition()
    {
        return '';
    }

    private function prsHtml(array $lines, $type, $start)
    {
        foreach ($lines as &$line) {
            $line = $this->prsInline($line,
                isset($this->_specialWhiteList[$type]) ? $this->_specialWhiteList[$type] : '');
        }

        return implode("\n", $this->markLines($lines, $start));
    }

    public function cleanUrl($url)
    {
        if (preg_match("/^\s*((http|https|ftp|mailto):[\p{L}_a-z0-9-:\.\*\/%#;!@\?\+=~\|\,&\(\)]+)/iu", $url, $matches)) {
            return $matches[1];
        } else if (preg_match("/^\s*([\p{L}_a-z0-9-:\.\*\/%#!@\?\+=~\|\,&]+)/iu", $url, $matches)) {
            return $matches[1];
        } else {
            return '#';
        }
    }

    public function escapeBracket($str)
    {
        return str_replace(
            array('\[', '\]', '\(', '\)'), array('[', ']', '(', ')'), $str
        );
    }

    private function start_block($type, $start, $value = NULL)
    {
        $this->_pos++;
        $this->_current = $type;

        $this->_blocks[$this->_pos] = array($type, $start, $start, $value);

        return $this;
    }

    private function end_block()
    {
        $this->_current = 'normal';
        return $this;
    }

    private function is_block($type, $value = NULL)
    {
        return $this->_current == $type
            && (NULL === $value ? true : $this->_blocks[$this->_pos][3] == $value);
    }

    private function get_block()
    {
        return isset($this->_blocks[$this->_pos]) ? $this->_blocks[$this->_pos] : NULL;
    }

    private function set_block($to = NULL, $value = NULL)
    {
        if (NULL !== $to) {
            $this->_blocks[$this->_pos][2] = $to;
        }

        if (NULL !== $value) {
            $this->_blocks[$this->_pos][3] = $value;
        }

        return $this;
    }

    private function back_block($step, $type, $value = NULL)
    {
        if ($this->_pos < 0) {
            return $this->start_block($type, 0, $value);
        }

        $last = $this->_blocks[$this->_pos][2];
        $this->_blocks[$this->_pos][2] = $last - $step;

        if ($this->_blocks[$this->_pos][1] <= $this->_blocks[$this->_pos][2]) {
            $this->_pos++;
        }

        $this->_current = $type;
        $this->_blocks[$this->_pos] = array(
            $type, $last - $step + 1, $last, $value
        );

        return $this;
    }

    private function combine_block()
    {
        if ($this->_pos < 1) {
            return $this;
        }

        $prev = $this->_blocks[$this->_pos - 1];
        $current = $this->_blocks[$this->_pos];

        $prev[2] = $current[2];
        $this->_blocks[$this->_pos - 1] = $prev;
        $this->_current = $prev[0];
        unset($this->_blocks[$this->_pos]);
        $this->_pos--;

        return $this;
    }
}
