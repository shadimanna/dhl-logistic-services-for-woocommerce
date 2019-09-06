<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Label')) :

class DHLPWC_Model_Logic_Label extends DHLPWC_Model_Core_Singleton_Abstract
{

    const FILE_PREFIX = 'dhlpwc-label-';
    const BATCH_FILE_PREFIX = 'dhlpwc-labels-';

    public function create_pdf_file($order_id, $base64_pdf)
    {
        $pdf = base64_decode($base64_pdf);
        $file_name = self::FILE_PREFIX . $order_id . '_' . str_shuffle((string)time() . rand(1000, 9999)) . '.pdf';
        $upload_path = wp_upload_dir();
        $path = $upload_path['path'] . DIRECTORY_SEPARATOR . $file_name;
        $url = $upload_path['url'] . '/' . $file_name;

        // TODO, handle errors
        //$file_save_status = file_put_contents($path, $pdf);
        file_put_contents($path, $pdf);

        return array(
            'url' => $url,
            'path' => $path
        );
    }

    public function combine_pdfs($order_ids, $orientation = null, $stack = 0)
    {
        $loader = DHLPWC_Libraryloader::instance();
        $pdf_merger = $loader->get_pdf_merger();
        if ($pdf_merger === null) {
            return null;
        }

        $files = 0;

        foreach ($order_ids as $order_id) {
            $meta_service = new DHLPWC_Model_Service_Order_Meta();
            $labels = $meta_service->get_labels($order_id);

            if (!empty($labels)) {

                foreach ($labels as $label_data) {
                    $label = new DHLPWC_Model_Meta_Order_Label($label_data);
                    $path = $label->pdf->path;
                    if (!file_exists($path)) {
                        $path = $this->restore_pdf_path($path);
                        if (!$path) {
                            // Could not fix
                            continue;
                        }
                    }
                    $pdf_merger->addPDF($path, 'all');
                    $files++;
                }
            }
        }

        if (!$files) {
            return null;
        }

        $order_id_tag = implode('_', $order_ids);
        $order_id_tag = substr($order_id_tag,0,20); // Limit the length if a lot of orders are selected

        $file_name = self::BATCH_FILE_PREFIX . $order_id_tag . '_' . str_shuffle((string)time() . rand(1000, 9999)) . '.pdf';
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['path'] . DIRECTORY_SEPARATOR . $file_name;
        $url = $upload_dir['url'] . '/' . $file_name;

        if (($orientation == 'L' || $orientation == 'P') && $stack > 0) {
            $pdf_merger->groupedMerge('file', $path, $orientation, $stack);
        } else {
            $pdf_merger->merge('file', $path);
        }

        return array(
            'url' => $url,
            'path' => $path
        );
    }

    protected function restore_pdf_path($path)
    {
        $upload_dir = wp_upload_dir();

        // This is an attempt to fix the path if backslashes have been removed by wordpress
        if (file_exists(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path))) {

            $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);

        } else {

            $stripped_path = str_replace(array('/', '\\'), '', $path);
            $stripped_upload_path = str_replace(array('/', '\\'), '', $upload_dir['basedir']);

            if (!substr($stripped_path, 0, strlen($stripped_upload_path)) === $stripped_upload_path) {
                // Upload base dir has since changed. Unfortunately impossible to determine the path now
                return null;
            }

            $end_path = substr($path, strlen($stripped_upload_path));

            if (!file_exists($upload_dir['basedir'] . DIRECTORY_SEPARATOR . $end_path)) {
                // End path seems incorrect. Attempt to fix it
                $number_start = strcspn($end_path, '0123456789');
                $end_path = substr($end_path, $number_start);

                $path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $end_path;
                $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);

                if (!file_exists($path)) {
                    // Final attempt, try to inject a DS after the date, before filename
                    $position = strpos($path, self::FILE_PREFIX);
                    $path = substr_replace($path, DIRECTORY_SEPARATOR, $position, 0);
                }
            }

        }

        if (!file_exists($path)) {
            // Still can't find it
            return null;
        }

        return $path;
    }

    public function delete_pdf_file($path)
    {
        if (!$this->validate_pdf_file($path)) {
            // Attempt to fix path
            $path = $this->restore_pdf_path($path);
            if (!$this->validate_pdf_file($path)) {
                return false;
            }
        }

        unlink($path);
        return true;
    }

    protected function validate_pdf_file($path)
    {
        $upload_dir = wp_upload_dir();
        if (!strpos($path, $upload_dir['basedir']) === 0) {
            return false;
        }

        $file = basename($path);
        if (!strpos($file, self::FILE_PREFIX) === 0) {
            return false;
        }

        $extension = 'pdf';
        if (!substr_compare($file, $extension, -strlen($extension)) === 0) {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        return true;
    }

}

endif;
