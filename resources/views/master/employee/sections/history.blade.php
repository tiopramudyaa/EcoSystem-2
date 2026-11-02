@include('partials.record-history', ['type' => 'employee', 'id' => $employeeId])
<script>
    (window.onSectionFirstShow || function (t, fn) { document.addEventListener('DOMContentLoaded', fn); })('history', function () {
        window.loadRecordHistory('recordHistory-employee-{{ $employeeId }}');
    });
</script>
