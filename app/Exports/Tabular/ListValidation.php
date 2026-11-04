<?php

namespace App\Exports\Tabular;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A dropdown (Excel list validation) on a range, so the sheet offers exactly the choices the form does
 * and rejects anything else — what is picked is what the importer can read.
 */
class ListValidation
{
    /** @param  array<int, string|int>  $list */
    public static function apply(Worksheet $sheet, string $range, array $list, string $what = 'value'): void
    {
        $formula = '"' . str_replace('"', '""', implode(',', $list)) . '"';

        if ($list === [] || strlen($formula) > 255) { // Excel's limit for an inline list
            return;
        }

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Not an option')
            ->setError("Pick a {$what} from the list.")
            ->setShowInputMessage(false)
            ->setFormula1($formula);

        $sheet->setDataValidation($range, $validation);
    }
}
