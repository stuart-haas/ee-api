<?php if(count($vars) > 1): ?>
  <table id="data-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Date</th>
        <th>Method</th>
        <th>URL</th>
        <th>Data</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($vars as $key => $value): ?>
        <?php if(isset($value['id'])): ?>
          <tr>
            <td><?= $value['id'] ?></td>
            <td><?= $value['date'] ?></td>
            <td><?= $value['method'] ?></td>
            <td><?= $value['url'] ?></td>
            <td><?= $value['data'] ?></td>
          </tr>
        <?php endif; ?>
      <?php endforeach ?>
    </tbody>
  </table>
<?php else: ?>
  <div class="box">
    <p style="margin-left: 1.5em;">There is no data available yet.</p>
  </div>
<?php endif; ?>