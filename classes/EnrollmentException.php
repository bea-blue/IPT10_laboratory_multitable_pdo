<?php
// classes/EnrollmentException.php
// Thrown for business-rule failures (no slots, duplicate enrollment, unknown
// class, ...). The message is safe to show to the administrator. Genuine
// database failures stay as PDOException and are NOT shown verbatim.
class EnrollmentException extends RuntimeException {
}
