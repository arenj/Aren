<?php
namespace Aren\Core;


class Page
{
    public static function make($currentPage, $totalRecords, $url, $pageSize = 10, $css = 'pagination')
    {
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $lang_prev = '上一页';
        $lang_next = '下一页';
        if ($totalRecords <= $pageSize) return '';
        $mult = '';
        $totalPages = ceil($totalRecords / $pageSize);
        $mult .= '<div class="' . $css . '">';
        $currentPage < 1 && $currentPage = 1;
        if ($currentPage > 1) {
            $mult .= '<a href="' . $url . 'page=' . ($currentPage - 1) . '">' . $lang_prev . '</a>';
        } else {
            $mult .= '<a class="disabled">' . $lang_prev . '</a>';
        }
        if ($totalPages < 13) {
            for ($counter = 1; $counter <= $totalPages; $counter++) {
                if ($counter == $currentPage) {
                    $mult .= '<a class="active">' . $counter . '</a>';
                } else {
                    $mult .= '<a href="' . $url . 'page=' . $counter . '">' . $counter . '</a>';
                }
            }
        } elseif ($totalPages > 11) {
            if ($currentPage < 7) {
                for ($counter = 1; $counter < 10; $counter++) {
                    if ($counter == $currentPage) {
                        $mult .= '<a class="active">' . $counter . '</a>';
                    } else {
                        $mult .= '<a href="' . $url . 'page=' . $counter . '">' . $counter . '</a>';
                    }
                }
                $mult .= '<a class="disabled">&#8230;</a><a href="' . $url . 'page=' . ($totalPages - 1) . '">' . ($totalPages - 1) . '</a><a href="' . $url . 'page=' . $totalPages . '">' . $totalPages . '</a>';
            } elseif ($totalPages - 6 > $currentPage && $currentPage > 6) {
                $mult .= '<a href="' . $url . 'page=1">1</a><a href="' . $url . 'page=2">2</a><a class="disabled">&#8230;</a>';
                for ($counter = $currentPage - 3; $counter <= $currentPage + 3; $counter++) {
                    if ($counter == $currentPage) {
                        $mult .= '<a class="active">' . $counter . '</a>';
                    } else {
                        $mult .= '<a href="' . $url . 'page=' . $counter . '">' . $counter . '</a>';
                    }
                }
                $mult .= '<a class="disabled">&#8230;</a><a href="' . $url . 'page=' . ($totalPages - 1) . '">' . ($totalPages - 1) . '</a><a href="' . $url . 'page=' . $totalPages . '">' . $totalPages . '</a>';
            } else {
                $mult .= '<a href="' . $url . 'page=1">1</a><a href="' . $url . 'page=2">2</a><a class="disabled">&#8230;</a>';
                for ($counter = $totalPages - 8; $counter <= $totalPages; $counter++) {
                    if ($counter == $currentPage) {
                        $mult .= '<a class="active">' . $counter . '</a>';
                    } else {
                        $mult .= '<a href="' . $url . 'page=' . $counter . '">' . $counter . '</a>';
                    }
                }
            }
        }
        if ($currentPage < $counter - 1) {
            $mult .= '<a href="' . $url . 'page=' . ($currentPage + 1) . '">' . $lang_next . '</a>';
        } else {
            $mult .= '<a class="disabled">' . $lang_next . '</a>';
        }
        $mult .= '</div>';
        return $mult;
    }
}