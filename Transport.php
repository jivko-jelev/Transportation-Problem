<?php

class Transport
{
    const OPEN = 'Open';
    const CLOSED = 'Closed';
    protected $type;
    protected $transport_costs;
    protected $producers;
    protected $consumers;
    protected $solves;
    protected $width;
    protected $height;
    protected $table;
    protected $worst_case;


    public static function add_error($index, $error)
    {
        $_SESSION['errors'][$index] = $error;
    }

    protected static function have_errors()
    {
        return isset($_SESSION['errors']) && count($_SESSION['errors']) > 0;
    }

    public function get_type()
    {
        return $this->type;
    }

    public static function create_transport($manufacturers_num, $consumers_num, &$manufacturers, &$consumers, array $transport_costs)
    {
        $manufacturers = explode(',', $manufacturers);
        if (count($manufacturers) != $manufacturers_num) {
            self::add_error('manufacturers-cells', 'Number of manufacturers is wrong');
        }
        foreach ($manufacturers as $key => $manufacturer) {
            $manufacturer = trim($manufacturer);
            if ($manufacturer == null) {
                self::add_error('manufacturers-cells', 'Number of manufacturers is wrong');
            } elseif (!ctype_digit($manufacturer)) {
                self::add_error('manufacturers-cells', '"' . $manufacturer . '" is not a number');
            } elseif ($manufacturer > PHP_INT_MAX) {
                self::add_error('manufacturers-cells', '"' . $manufacturer . '" is bigger than the largest integer');
            } else {
                $manufacturers[$key] = $manufacturer;
            }
        }

        $consumers = explode(',', $consumers);
        if (count($consumers) != $consumers_num) {
            self::add_error('consumers-cells', 'Number of consumers is wrong');
        }
        foreach ($consumers as $key => $consumer) {
            $consumer = trim($consumer);
            if ($consumer == null) {
                self::add_error('consumers-cells', 'Number of consumers is wrong');
            } elseif (!ctype_digit($consumer)) {
                self::add_error('consumers-cells', '"' . $consumer . '" is not a number');
            } elseif ($consumer > PHP_INT_MAX) {
                self::add_error('consumers-cells', '"' . $consumer . '" is bigger than the largest integer');
            } else {
                $consumers[$key] = $consumer;
            }
        }

        if (count($transport_costs) != $manufacturers_num) {
            self::add_error('transport costs', 'Number of rows in transport costs table must be equal to the number of manufacturers');
        } elseif (count($transport_costs[0]) != $consumers_num) {
            self::add_error('transport costs', 'Number of columns in transport costs table must be equal to the number of consumers');
        } elseif (!self::have_errors()) {
            return new Transport($manufacturers, $consumers, $transport_costs);
        }
        return null;
    }

    //Max for 2-dimension array
    protected static function string_to_int($arr)
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key1 => $value1) {
                    $arr[$key][$key1] = (int)$value1;
                }
            } else {
                $arr[$key] = (int)$value;
            }
        }
        return $arr;
    }


    protected function __construct(array $manufacturers, array $consumers, array $transport_costs)
    {
        $this->producers = $this->string_to_int($manufacturers);
        $this->consumers = $this->string_to_int($consumers);
        $this->transport_costs = self::string_to_int($transport_costs);
        $this->width = count($consumers);
        $this->height = count($manufacturers);
        $this->setType();
    }

    protected function setType()
    {
        $this->type = array_sum($this->producers) == array_sum($this->consumers) ? self::CLOSED : self::OPEN;
    }

    protected function calc_z($support_plan)
    {
        $z = 0;
        foreach($support_plan as $row=>$value) {
            foreach($value as $col=>$value1) {
                $z += $support_plan[$row][$col] * $this->transport_costs[$row][$col];
            }
        }
        return $z;
    }

    protected function calc_index_estimates($support_plan)
    {
        $index_estimates = [];
        for ($i = 0; $i < count($this->producers); $i++) {
            for ($j = 0; $j < count($this->consumers); $j++) {
                if (!isset($support_plan[$i][$j])) {
                    $index_estimates[$i][$j] = 0;
                }
            }
        }
    }

    protected function row_with_more_full_cells($support_plan)
    {
        $max = 0;
        for ($i = 0; $i < count($this->producers); $i++) {
            if (count($support_plan[$i]) > count($support_plan[$max])) {
                $max = $i;
            }
        }
        return $max;
    }

    protected function col_with_more_full_cells($support_plan)
    {
        $max = 0;
        $index = 0;
        for ($j = 0; $j < count($this->transport_costs[0]); $j++) {
            $count = 0;
            for ($i = 0; $i < count($this->transport_costs); $i++) {
                if (isset($support_plan[$i][$j])) {
                    $count++;
                }
            }
            if ($count > $max) {
                $index = [$j => $count];
                $max = $count;
            }
        }
        return $index;
    }

    protected function find_potentials($support_plan, $u, $v)
    {
        $index_estimates = [];
        for ($i = 0; $i < count($this->producers); $i++) {
            for ($j = 0; $j < count($this->consumers); $j++) {
                if (!isset($support_plan[$i][$j])) {
                    $index_estimates[$i][$j] = (int)($u[$i] + $v[$j] - $this->transport_costs[$i][$j]);
                }
            }
        }
        return $index_estimates;
    }

    protected function have_empty_cells($u, $v)
    {
        return (count($u) != count($this->producers) || count($v) != count($this->consumers));
    }

    protected function calc_u_v($support_plan, &$u, &$v)
    {
        $col_full_cells = $this->col_with_more_full_cells($support_plan);
        $row_full_cells = $this->row_with_more_full_cells($support_plan);

        if (count($support_plan[$row_full_cells]) >= $col_full_cells[array_keys($col_full_cells)[0]]) {
            $u[$row_full_cells] = 0;
        } else {
            $v[array_keys($col_full_cells)[0]] = 0;
        }

        while ($this->have_empty_cells($u, $v)) {
            for ($i = 0; $i < count($this->consumers); $i++) {
                if (isset($v[$i])) {
                    for ($j = 0; $j < count($this->producers); $j++) {
                        if (isset($support_plan[$j][$i])) {
                            $u[$j] = $this->transport_costs[$j][$i] - $v[$i];
                        }
                    }
                }
            }

            for ($i = 0; $i < count($this->producers); $i++) {
                if (isset($u[$i])) {
                    for ($j = 0; $j < count($this->consumers); $j++) {
                        if (isset($support_plan[$i][$j])) {
                            $v[$j] = $this->transport_costs[$i][$j] - $u[$i];
                        }
                    }
                }
            }
        }
    }

    protected function worst_case_index_score($potentials)
    {
        $max = null;

        foreach ($potentials as $key1 => $potential) {
            foreach ($potential as $key2 => $p) {
                if ($p > 0) {
                    if ($max == null || $potentials[$max[0]][$max[1]] < $p) {
                        $max[0] = $key1;
                        $max[1] = $key2;
                    }
                }
            }
        }
        return $max;
    }


    protected function another_optimal_plan($potentials)
    {
        $max = null;
        foreach ($potentials as $key1 => $potential) {
            foreach ($potential as $key2 => $p) {
                if ($p == 0) {
                    $max[] = [$key1, $key2];
                }
            }
        }
        return $max;
    }

    protected function calc_t_recursion($support_plan, &$dots, $allowed_directions)
    {
        foreach ($allowed_directions as $direction) {
            $end = false;
            $temp_location = $dots[count($dots) - 1];

            switch ($direction) {
                case 0:
                case 2:
                    $next_directions = [1, 3];
                    break;
                default:
                    $next_directions = [0, 2];
                    break;
            }
            while (!$end) {
                switch ($direction) {
                    case 0:
//                        echo 'Посока нагоре';
                        if ($temp_location[0] > 0) {
                            $temp_location[0]--;
                        } else {
                            $end = true;
                        }
                        break;
                    case 1:
//                        echo 'Посока надясно';
                        if ($temp_location[1] < count($this->consumers)) {
                            $temp_location[1]++;
                        } else {
                            $end = true;
                        }
                        break;
                    case 2:
//                        echo 'Посока надолу';
                        if ($temp_location[0] < count($this->producers)) {
                            $temp_location[0]++;
                        } else {
                            $end = true;
                        }
                        break;
                    case 3:
//                        echo 'Посока наляво';
                        if ($temp_location[1] > 0) {
                            $temp_location[1]--;
                        } else {
                            $end = true;
                        }
                        break;
                }
                if (!$end && isset($support_plan[$temp_location[0]][$temp_location[1]])) {
                    $temp = $dots;
                    $dots[] = $temp_location;
                    if ($this->calc_t_recursion($support_plan, $dots, $next_directions)) {
                        return true;
                    } else {
                        $dots = $temp;
                    }
                }

                if (!$end && $dots[0][0] == $temp_location[0] && $dots[0][1] == $temp_location[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function calc_t($support_plan, $start_from)
    {
        $start_from_[0] = $start_from;
        if ($this->calc_t_recursion($support_plan, $start_from_, [0, 1, 2, 3])) {
            return $start_from_;
        }
    }

    protected function have_t($t, $row, $col)
    {
        foreach ($t as $key => $value) {
            if ($value[0] == $row && $value[1] == $col) {
                return $key & 1 == 1 ? '<span class="minus-t">-t</span>' : '<span class="plus-t">+t</span>';
            }
        }
    }

    protected function min_t($support_plan, $t)
    {
        $min_t = $support_plan[$t[1][0]][$t[1][1]];
        for ($i = 3; $i < count($t); $i += 2) {
            if ($support_plan[$t[$i][0]][$t[$i][1]] < $min_t) {
                $min_t = $support_plan[$t[$i][0]][$t[$i][1]];
            }
        }
        return $min_t;
    }

    protected function recalc_support_plan(&$support_plan, $t)
    {
        //Намираме по-малкото t, ако има 2 t-ta с тази стойност, след преизчисляване на пълните клетки
        //се премахва това, което е с по-голям транспортен разход
        $min_t = $this->min_t($support_plan, $t);
        $count_t = 0;
        $equal_ts = [];
        foreach ($t as $key => $ts) {
            if ($key & 1 == 1 && $support_plan[$ts[0]][$ts[1]] == $min_t) {
                $count_t++;
                $equal_ts[] = $ts;
            }
        }

        $bigger_transport_cost = null;
        foreach ($equal_ts as $key => $ts) {
            if ($this->transport_costs[$ts[0]][$ts[1]] > $bigger_transport_cost) {
                $must_remove_index = $ts;
            }
        }

        foreach ($t as $key => $ts) {
            if ($key & 1 == 1) {
                $support_plan[$ts[0]][$ts[1]] -= $min_t;
                if (($count_t == 2 && $must_remove_index == $ts) || ($count_t == 1 && $support_plan[$ts[0]][$ts[1]] === 0)) {
                    unset($support_plan[$ts[0]][$ts[1]]);
                }
            } else {
                if (!isset($support_plan[$ts[0]][$ts[1]])) {
                    $support_plan[$ts[0]][$ts[1]] = 0;
                }
                $support_plan[$ts[0]][$ts[1]] += $min_t;
            }
        }
    }

    protected function print_table($table_number, $support_plan, $u, $v, $t = null)
    {
        echo '<table class="table table-bordered">';
        echo '<tr><td>№' . $table_number . '</td>';
        for ($j = 0; $j < count($this->consumers); $j++) {
            echo "<td>B<sub>" . ($j + 1) . "</sub><br>v<sub>" . ($j + 1) . "</sub>=$v[$j]</td>";
        }
        echo '<td>a<sub>i</sub></td></tr>';
        for ($i = 0; $i < count($this->producers); $i++) {
            echo '<tr><td>A<sub>' . ($i + 1) . '</sub><br>u<sub>' . ($i + 1) . '</sub>=' . $u[$i] . '</td>';
            for ($j = 0; $j < count($this->consumers); $j++) {
                echo '<td class="inside-cells"><span class="load">' .
                    (isset($support_plan[$i][$j]) ? $support_plan[$i][$j] : ((isset($t) && $t[0][0] == $i && $t[0][1] == $j) ? '' : '&emsp;–')) .
                    (isset($t) ? $this->have_t($t, $i, $j) : '') . '</span><div class="transport pull-right">' . $this->transport_costs[$i][$j] .
                    '</div></td>';
            }
            echo "<td>{$this->producers[$i]}</td>";
            echo '</tr>';
        }
        echo '<tr><td>b<sub>j</sub></td>';
        for ($j = 0; $j < count($this->consumers); $j++) {
            echo "<td>{$this->consumers[$j]}</td>";
        }
        if (isset($this->solves) && count($this->solves) > 0) {
            echo "<td>Z<sub>" . ($table_number - count($this->solves)) . "</sub>={$this->calc_z($support_plan)}</td></tr>";
        } else {
            echo "<td>Z<sub>$table_number</sub>={$this->calc_z($support_plan)}</td></tr>";
        }
        echo '</table>';
    }

    protected function num_full_cells($support_plan)
    {
        $count = 0;
        foreach ($support_plan as $value) {
            foreach ($value as $value1) {
                $count++;
            }
        }
        return $count;
    }

    protected function insert_zero(&$support_plan)
    {
        for ($i = 0; $i < count($this->producers); $i++) {
            for ($j = 0; $j < count($this->consumers); $j++) {
                if (isset($support_plan[$i][$j]) && $this->producers[$i] == $this->consumers[$j]) {
                    for ($k = 0; $k < count($this->producers); $k++) {
                        if ($k != $i && !isset($support_plan[$k][$j])) {
                            $support_plan[$k][$j] = 0;
                            return;
                        }
                    }
                    for ($k = 0; $k < count($this->consumers); $k++) {
                        if ($k != $j && !isset($support_plan[$i][$k])) {
                            $support_plan[$i][$k] = 0;
                            return;
                        }
                    }
                }
            }
        }
    }

    protected function my_array_sum($arr, $index)
    {
        return isset($arr[$index]) ? array_sum($arr[$index]) : 0;
    }

    protected function insert_zero_in_first_empty_cell(&$support_plan)
    {
        for ($i = 0; $i < count($this->transport_costs[0]); $i++) {
            for ($j = 0; $j < count($this->transport_costs); $j++) {
                if (!isset($support_plan[$j][$i])) {
                    $support_plan[$j][$i] = 0;
                    return;
                }
            }
        }
    }

    protected function add_new_row_or_column(&$support_plan)
    {
        if (array_sum($this->producers) > array_sum($this->consumers)) {
            $col = count($this->consumers);
            for ($i = 0; $i < count($this->producers); $i++) {
                $this->transport_costs[$i][$col] = 0;
            }
            $row_value = array_sum($this->producers) - array_sum($this->consumers);
            $this->consumers[] = $row_value;


            for ($i = 0; $i < count($this->transport_costs); $i++) {
                if ($this->my_array_sum($support_plan, $i) < $this->producers[$i]) {
                    $support_plan[$i][count($this->transport_costs[0]) - 1] = $this->producers[$i] - $this->my_array_sum($support_plan, $i);
                }
            }

        } else {
            $row = count($this->producers);
            for ($i = 0; $i < count($this->consumers); $i++) {
                $this->transport_costs[$row][$i] = 0;
            }
            $this->producers[] = array_sum($this->consumers) - array_sum($this->producers);

            for ($i = 0; $i < count($support_plan); $i++) {
                foreach ($support_plan[$i] as $key => $value) {
                    if (!isset($col_value[$key])) {
                        $col_value[$key] = 0;
                    }
                    $col_value[$key] += $value;
                }
            }


            //намирам какъв товар да добавя в клетките на добавения ред
            $row_value = $this->producers[$row];
            for ($i = 0; $i < count($this->consumers); $i++) {
                if (!isset($col_value[$i])) {
                    $col_value[$i] = 0;
                }
            }

            foreach ($col_value as $key => $value) {
                if ($value != $this->consumers[$key]) {
                    $temp = $this->consumers[$key] - $value;
                    $support_plan[$row][$key] = $temp;
                    $row_value -= $temp;
                    if ($row_value == 0) {
                        break;
                    }
                }
            }
        }
    }

    protected static function sum_all_cells($support_plan)
    {
        $sum = 0;
        foreach ($support_plan as $row => $row_num) {
            foreach ($row_num as $col => $value) {
                $sum += $value;
            }
        }
        return $sum;
    }

    protected function print_potentials($potentials, $plan)
    {
        $potentials_text = [];
        foreach ($potentials as $key1 => $potential) {
            foreach ($potential as $key2 => $p) {
                if ($plan !== null && $plan[0] == $key1 && $plan[1] == $key2) {
                    $potentials_text[] = '<span class="worst-case">' . 'D<sub>' . ($key1 + 1) . ',' . ($key2 + 1) . '</sub>=' . ($p > 0 ? '<span class="error-normal">' . $p . '&gt;0</span>' : $p) . '</span>';
                } else {
                    $potentials_text[] = 'D<sub>' . ($key1 + 1) . ',' . ($key2 + 1) . '</sub>=' . ($p > 0 ? '<span class="error-normal">' . $p . '&gt;0</span>' : $p);
                }
            }
        }
        echo '<div class="potentials"><span>' . implode(', ', $potentials_text) . '</span></div>';
    }

    protected function print_min_t($support_plan, $t)
    {
        $html = '<span>t = min{';
        for ($i = 1; $i < count($t); $i += 2) {
            $arr[] = $support_plan[$t[$i][0]][$t[$i][1]];
        }
        $html .= implode(',', $arr);
        $html .= '}=<b>' . min($arr) . '</b>';
        $html .= '</span>';
        echo $html;
    }

    protected function print_support_plans($support_plan)
    {
        $this->solves = null;

        $u = $v = [];
        $this->calc_u_v($support_plan, $u, $v);
        $table_number = 1;
        $this->print_table($table_number, $support_plan, $u, $v);

        do {
            $potentials = $this->find_potentials($support_plan, $u, $v);
            $worst_case = $this->worst_case_index_score($potentials);

            //+t -t
            if ($worst_case != null) {
                $this->print_potentials($potentials, $worst_case);

                $t = $this->calc_t($support_plan, $worst_case);
                $this->print_table(++$table_number, $support_plan, $u, $v, $t);
                $this->print_min_t($support_plan, $t);
                $this->recalc_support_plan($support_plan, $t);
                $u = $v = [];
                $this->calc_u_v($support_plan, $u, $v);
                $this->print_table($table_number, $support_plan, $u, $v);
            }
        } while ($worst_case != null);

        $this->solves[] = $support_plan;
        $base_plan = $support_plan;
        $another_optimal_plan = $this->another_optimal_plan($potentials);
        if ($another_optimal_plan) {
            foreach ($another_optimal_plan as $plan) {
                $support_plan = $base_plan;
                $potentials = $this->find_potentials($support_plan, $u, $v);
                $this->print_potentials($potentials, $plan);

                $t = $this->calc_t($support_plan, $plan);
                $this->print_table(++$table_number, $support_plan, $u, $v, $t);
                $this->recalc_support_plan($support_plan, $t);
                $u = $v = [];
                $this->calc_u_v($support_plan, $u, $v);
                $this->print_table($table_number, $support_plan, $u, $v);
                $this->solves[] = $support_plan;
            }
        } else {
            $this->print_potentials($potentials, $support_plan);
        }
        $this->print_solves();
    }


    protected function print_solves()
    {
        $html = "<hr><p class='solves-z'>Z<sub>min</sub>={$this->calc_z($this->solves[0])}</p>";
        if ($this->solves) {
            foreach ($this->solves as $key => $solve) {
                $html .= '<div class="div-solves"><table class="solves">';
                foreach ($this->producers as $row => $manufacturer) {
                    $html .= '<tr>';
                    if ($row == 0) {
                        $html .= '<td class="first" rowspan="' . count($this->producers) . '">X' . ($key > 0 ? '<sup>' . "'" . '</sup>' : '') . '<sub>opt</sub>=</td>';
                    }
                    foreach ($this->consumers as $col => $consumer) {
                        $html .= '<td>';

                        // top-left, top-right, bottom-left and bottom-right round border of matrix
                        if ($row == 0 && $col == 0) {
                            $html .= '<div class="div-left" style="border-top-left-radius: 10px; ">';
                        } elseif ($row == count($this->producers) - 1 && $col == 0) {
                            $html .= '<div class="div-left" style="border-bottom-left-radius: 10px; ">';
                        } elseif ($col == 0) {
                            $html .= '<div class="div-left" >';
                        } elseif ($row == 0 && $col == count($this->consumers) - 1) {
                            $html .= '<div class="div-right" style="border-top-right-radius: 10px; ">';
                        } elseif ($row == count($this->producers) - 1 && $col == count($this->consumers) - 1) {
                            $html .= '<div class="div-right" style="border-bottom-right-radius: 10px; ">';
                        } elseif ($col == count($this->consumers) - 1) {
                            $html .= '<div class="div-right">';
                        }


                        if ($this->type == self::OPEN && (($this->height == $row && $row == count($this->producers) - 1)
                                || ($this->width == $col && $col == count($this->consumers) - 1))) {
                            $html .= '<span class="error">';
                        }
                        $html .= (isset($solve[$row][$col]) ? $solve[$row][$col] : '–');
                        if ($col == 0 || $col == count($this->consumers) - 1) {
                            $html .= '</div>';
                        }
                        if ($this->type == self::OPEN) {
                            $html .= '</span>';
                        }
                        $html .= '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
                if ($this->type == self::OPEN) {
                    if (($this->height == $row && $row == count($this->producers) - 1)) {
                        for ($i = 0; $i < count($this->transport_costs[0]); $i++) {
                            if (isset($solve[count($this->transport_costs) - 1][$i])) {
                                $html .= "<p>Consumer B<sub>" . ($i + 1) . "</sub> will not receive " . ($solve[count($this->transport_costs) - 1][$i]) . " units of requested production.</p>";
                            }
                        }
                    } else {
                        for ($i = 0; $i < count($this->transport_costs); $i++) {
                            if (isset($solve[$i][count($this->transport_costs[0]) - 1])) {
                                $html .= "<p>At the producer A<sub>" . ($i + 1) . "</sub> there will be left " . ($solve[$i][count($this->transport_costs[0]) - 1]) . " units of production.</p>";
                            }
                        }
                    }
                }
                $html .= '</div>';
                if ($key != count($this->solves) - 1 && $this->type == 'Open') {
                    $html .= '<hr>';
                }
            }
            echo $html;
        }
    }

    public function solve_with_minimum_cost()
    {
        $this->print_support_plans($this->get_initial_support_plan_with_minimum_cost());
    }

    public function solve_with_northwest_corner()
    {
        $this->print_support_plans($this->get_initial_support_plan_with_northwest_corner());
    }

    protected function reformat_support_plan(&$support_plan)
    {
        if ($this->type == self::OPEN) {
            $this->add_new_row_or_column($support_plan);
        }
        while ($this->num_full_cells($support_plan) < count($this->transport_costs) + count($this->transport_costs[0]) - 1) {
            $temp_support_plan = $support_plan;
            $this->insert_zero($support_plan);
            if ($temp_support_plan == $support_plan) {
                break;
            }
        }

        while ($this->num_full_cells($support_plan) < count($this->transport_costs) + count($this->transport_costs[0]) - 1) {
            $this->insert_zero_in_first_empty_cell($support_plan);
        }
    }

    protected function get_initial_support_plan_with_northwest_corner()
    {
        $producers = $this->producers;
        $consumers = $this->consumers;
        $support_plan = [];
        for ($row = 0; $row < $this->height; $row++) {
            for ($col = 0; $col < $this->width; $col++) {
                if ($producers[$row] > 0 && $consumers[$col] > 0) {
                    if ($producers[$row] >= $consumers[$col]) {
                        $support_plan[$row][$col] = $consumers[$col];
                        $consumers[$col] = 0;
                        $producers[$row] -= $support_plan[$row][$col];
                    } else {
                        $support_plan[$row][$col] = $producers[$row];
                        $producers[$row] = 0;
                        $consumers[$col] -= $support_plan[$row][$col];
                    }
                }
            }
        }

        $this->reformat_support_plan($support_plan);
        return $support_plan;
    }

    protected function get_initial_support_plan_with_minimum_cost()
    {
        $m = $this->producers;
        $c = $this->consumers;
        $support_plan = [];
        foreach ($this->sort_transport_costs() as $transport_cost) {
            for ($i = 0; $i < count($this->producers); $i++) {
                for ($j = 0; $j < count($this->consumers); $j++) {
                    if ($this->transport_costs[$i][$j] == $transport_cost && $m[$i] > 0 && $c[$j] > 0) {
                        if ($m[$i] >= $c[$j]) {
                            $support_plan[$i][$j] = (int)$c[$j];
                            $m[$i] -= $c[$j];
                            $c[$j] = 0;
                        } else {
                            $support_plan[$i][$j] = (int)$m[$i];
                            $c[$j] -= $m[$i];
                            $m[$i] = 0;
                        }
                    }
                }
            }
        }

        $this->reformat_support_plan($support_plan);
        return $support_plan;
    }

    protected function sort_transport_costs()
    {
        $sorted_array = [];
        foreach ($this->transport_costs as $transport_cost) {
            foreach ($transport_cost as $transport) {
                $sorted_array[$transport] = $transport;
            }
        }
        sort($sorted_array);
        return $sorted_array;
    }
}