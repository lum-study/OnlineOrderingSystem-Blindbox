<?php

class SimplePager extends Database
{
    public $limit;
    public $page;
    public $item_count;
    public $page_count;
    public $result;
    public $count;

    public function __construct($query, $params = [], $limit = 10, $page = 1)
    {
        // Validate limit & page
        $this->limit = max((int) $limit, 1);
        $this->page = max((int) $page, 1);

        // SAFE count query (no regex)
        $countQuery = "SELECT COUNT(*) AS total FROM ($query) AS t";

        $countRow = self::fetch($countQuery, $params);
        $this->item_count = $countRow['total'] ?? 0;

        // Page count
        $this->page_count = max(ceil($this->item_count / $this->limit), 1);

        // Offset
        $offset = ($this->page - 1) * $this->limit;

        // Add LIMIT (safe because integers are validated)
        $pagedQuery = $query . " LIMIT $offset, $this->limit";

        // Fetch results
        $this->result = self::fetchAll($pagedQuery, $params);

        $this->count = count($this->result);
    }

    public function html($href = '', $attr = '')
    {
        if ($this->page_count <= 1) {
            return; // No pagination needed
        }

        $prev = max($this->page - 1, 1);
        $next = min($this->page + 1, $this->page_count);

        echo "<nav class='pager' $attr>";
        echo "<div class='pager-group'>";

        // Previous button
        $leftIcon = file_get_contents(__DIR__ . "/../assets/images/icons/fa-angles-left.svg");
        echo "<a href='?page=$prev&$href' class='pager-item pager-prev'" . ($this->page == 1 ? " disabled" : "") . ">$leftIcon</a>";

        // Page numbers logic
        if ($this->page_count <= 3) {
            // Show all pages if 3 or less
            for ($p = 1; $p <= $this->page_count; $p++) {
                $active = $p == $this->page ? " active" : "";
                echo "<a href='?page=$p&$href' class='pager-item$active'>$p</a>";
            }
        } else {
            // Show 1, 2, ..., last page
            $active1 = $this->page == 1 ? " active" : "";
            $active2 = $this->page == 2 ? " active" : "";
            $activeLast = $this->page == $this->page_count ? " active" : "";

            echo "<a href='?page=1&$href' class='pager-item$active1'>1</a>";
            echo "<a href='?page=2&$href' class='pager-item$active2'>2</a>";

            if ($this->page_count > 3) {
                echo "<span class='pager-ellipsis'>...</span>";
            }

            echo "<a href='?page={$this->page_count}&$href' class='pager-item$activeLast'>{$this->page_count}</a>";
        }

        // Next button
        $rightIcon = file_get_contents(__DIR__ . "/../assets/images/icons/fa-angles-right.svg");
        echo "<a href='?page=$next&$href' class='pager-item pager-next'" . ($this->page == $this->page_count ? " disabled" : "") . ">$rightIcon</a>";

        echo "</div>";
        echo "</nav>";
    }
}
