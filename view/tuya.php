<?php

use Helpers\Html;

$data = Html::getTData($_GET);
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= str_replace('_', ' ', $data->dev->get('params.name')) ?> Chart</title>
  <link rel=stylesheet type="text/css" href="res/tuya.css?v=202603261630">
</head>

<body id="body" class="dark">

  <section id="current">
    <a id="back" href="/">
      <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.6569 10H10M10 10V15.6569M10 10L18.364 18.364M20.8278 13.7568C21.3917 10.9096 20.5704 7.84251 18.364 5.63604C14.8492 2.12132 9.15076 2.12132 5.63604 5.63604C2.12132 9.15076 2.12132 14.8492 5.63604 18.364C7.84251 20.5704 10.9096 21.3917 13.7568 20.8278" stroke="#777" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Back
    </a>
    <label id="status" for="showMore" class="show_toggle" style="background-color:dark<?= $data->dev->class()::COLORS[$data->dev->get('status')] ?>">
      <h1>
        <?php if ($data->current->online === 'true'): ?>
          <input type="checkbox" id="showMore" name="show_more" <?= Html::skip('show_more') ? 'checked' : '' ?>>
          <?php foreach ($data->current->fields as $fields): ?>
            <?php $decimals = max(array_map(fn($i) => strlen(substr(strrchr((string)$i->value, "."), 1)), $fields)) ?>
            <center>
              <?php foreach ($fields as $k => $field) if (!empty($field)): ?>
                <div class="field">
                  <?php if (isset($field->name)): ?>
                    <span class="name" style="color:<?= $field->color ?? 'white' ?>"><?= $field->name ?>:</span>
                  <?php endif ?>
                  <strong><?= number_format($field->value, $decimals) ?></strong>
                  <?php if (isset($field->suffix)): ?>
                    <span class="suffix"><?= $field->suffix ?></span>
                  <?php endif ?>
                </div>
              <?php endif ?>
            </center>
          <?php endforeach ?>
        <?php else: ?>
          Off
        <?php endif ?>
      </h1>
    </label>
    <center>
      Updated at
      <?= str_replace(' ', '&nbsp;', date_create($data->current->date)->format('Y-m-d H:i')) ?>
    </center>
    <?php foreach ($data->dev->get('dates') as $k => $i): ?>
      <?php if (!empty($i)): ?>
        <center style="color:<?= $data->dev->class()::COLORS[$k] ?>">
          Last&nbsp;<?= $data->dev->class()::STATUSES[$k] ?>:
          <?= str_replace(' ', '&nbsp;', date_create($i)->format('Y.m.d\&\n\b\s\p;H:i')) ?>
          (<?= str_replace(' ', '&nbsp;', date_create()->diff(date_create($i))->format('%ad&nbsp;%H:%I')) ?>&nbsp;ago).
        </center>
      <?php endif ?>
    <?php endforeach ?>
  </section>

  <section id="chart">
    <form id="dates" action="/" method="get">
      <a class="left" href="?<?= $data->urls->back ?>">
        <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path opacity="0.15" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" fill="#777" />
          <path d="M7 12H17M7 12L11 8M7 12L11 16M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </a>
      <div id="presets">
        <?php foreach ($data->urls->buttons as $name => $url): ?>
          <input type="button" value="<?= $name ?>" onclick="window.location.href='?<?= $url ?>'">
        <?php endforeach ?>
      </div>
      <input name="chart" type="hidden" value="<?= $data->dev->get('name') ?>">
      <input name="from" type="datetime-local" value="<?= $data->from ?>">
      <span>-</span>
      <input name="to" type="datetime-local" value="<?= $data->to ?>">
      <input type="submit" value="Go">
      <a class="right" href="?<?= $data->urls->fwd ?>">
        <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path opacity="0.15" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" fill="#777" />
          <path d="M17 12H7M17 12L13 16M17 12L13 8M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </a>
    </form>

    <?php foreach ($data->charts as $key => $chart): ?>
      <?php if (empty($chart->ranges)): ?>
        <div id="empty">No data!</div>
      <?php else: ?><br>
        <div id="chart_<?= $key ?>" class="chart" data-json='<?= json_encode($chart->chart) ?>'><canvas></canvas></div>
        <?php $ranges = array_filter($chart->ranges, fn($i)
        => strpos(strtolower($i->title), 'total') === false) ?>
        <?php if (count($ranges) > 1): ?>
          <center><?= min(array_column($ranges, 'min')) ?> - <?= max(array_column($ranges, 'max')) ?></center>
        <?php endif ?>
        <?php foreach ($chart->ranges as $k => $range): ?>
          <label class="show_toggle">
            <input type="checkbox" id="showMore" name="show_<?= $chart->key ?>_<?= $range->key ?>"
              <?= Html::skip("show_{$chart->key}_{$range->key}") ? 'checked' : '' ?> data-reload="true">
            <center class="min_max" style="color:<?= $chart->chart->colors[$k] ?>">
              <?= $range->title ?>: <?= $range->min ?> - <?= $range->max ?><?php if (!empty($range->on)): ?>,
              on: <?= $range->on ?><?php endif ?><?php if (!empty($range->off)): ?>,
              off: <?= $range->off ?><?php endif ?>
              (entries: <?= $range->count  ?>).
            </center>
          </label>
        <?php endforeach ?>
      <?php endif ?>
    <?php endforeach ?>

    <script src="res/chart.min.js"></script>
    <script>
      let toggle = '.show_toggle input[type=checkbox]';
      document.querySelectorAll(toggle).forEach(function(el) {
        el.addEventListener('change', function(event) {
          var cookie = Array.from(document.querySelectorAll(toggle + ':checked'))
            .map((i) => i.name).join('||');
          setCookie('toggle_inputs', cookie);
          if (event.target.dataset.reload == 'true') window.location.reload();
        });
      });

      var match = window.matchMedia || window.msMatchMedia;
      if (match ? match('(pointer:coarse)').matches : false)
        document.querySelectorAll('[type="datetime-local"]').forEach((el) =>
          el.addEventListener('change', (e) => e.target.form.submit()));

      <?php
      $a = ['year' => 'numeric', 'month' => 'numeric', 'day' => 'numeric', 'hour' => 'numeric', 'minute' => 'numeric', 'hour12' => false];
      $d = ['hour' => 'numeric', 'minute' => 'numeric', 'hour12' => false];
      $dates = ['year' => $a, 'month' => $a, 'day' => $d, 'hour' => $d, 'minute' => $d, 'tooltip' => $a];
      ?>
      Array.from(document.getElementsByClassName('chart')).forEach(function(el) {
        c = new Chart(JSON.parse(el.dataset.json));
        c.dateStyles = <?= json_encode($dates) ?>;
        c.render();
      });

      function setCookie(key, val = null, age = 0, site = 'lax') {
        dom = document.domain.split('.');
        dom[0] = '';

        cookie = key + '=' + val +
          '; domain=' + dom.join('.') +
          '; SameSite=' + site;

        if (age != 0) cookie += '; max-age=' + age;

        document.cookie = cookie
      }
    </script>
  </section>
</body>

</html>
