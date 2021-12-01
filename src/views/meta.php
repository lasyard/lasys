<span class="nobr">
    Created at <?php echo date('Y.m.d h:i:s', $time); ?> by <?php echo $user; ?>
</span>
<?php if ($edit) { ?>
    <span class="nobr">
        <i id='btn-delete' class="bi bi-x-square" style="float:right"></i>
    </span>
    <script>
        byId('btn-delete').addEventListener('click', () => {
            const r = confirm('Are you sure to delete "<?php echo $name; ?>"?');
            if (r) {
                Ajax.delete(function(res, type) {
                    alert(res);
                });
            }
        });
    </script>
<?php } ?>
