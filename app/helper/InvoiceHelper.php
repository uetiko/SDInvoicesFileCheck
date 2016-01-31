<?php
namespace helper;

/**
 * Class InvoiceHelper
 * @package helper
 */
class InvoiceHelper {
    /**
     * The got invoice is still new
     *
     * @const INVOICE_NEW
     */
    const INVOICE_NEW = 0;

    /**
     * The invoice has been signed by the third party
     *
     * @const INVOICE_SIGNED
     */
    const INVOICE_SIGNED = 1;

    /**
     * The invoice has already a pdf representation
     *
     * @const INVOICE_WITH_REPRESENTATION
     */
    const INVOICE_WITH_REPRESENTATION = 2;

    /**
     * Something went wrong and the invoice couldnt be signed
     *
     * @const INVOICE_NOVALID
     */
    const INVOICE_NOVALID = 3;

    /**
     * The invoice has been canceled by the user
     *
     * @const INVOICE_CANCELED
     */
    const INVOICE_CANCELED = 4;

    /**
     * The invoice is and should be on wait list to get signed
     *
     * @const INVOICE_WAITING
     */
    const INVOICE_WAITING = 5;

    /**
     * The invoice is and should be on wait list to get canceled
     *
     * @const INVOICE_WAITING_CANCELATION
     */
    const INVOICE_WAITING_CANCELATION = 6;

    static final public function getStatusByCode($code){
        $status = null;
        switch($code){
            case self::INVOICE_WITH_REPRESENTATION;
                $status = 'The invoice has already a pdf representation';
                break;
            case self::INVOICE_CANCELED:
                $status = 'The invoice has been canceled by the user';
                break;
            case self::INVOICE_NEW:
                $status = 'The got invoice is still new';
                break;
            case self::INVOICE_NOVALID:
                $status = 'Something went wrong and the invoice couldnt be signed';
                break;
            case self::INVOICE_SIGNED:
                $status = 'The invoice has been signed by the third party';
                break;
            case self::INVOICE_WAITING:
                $status = 'The invoice is and should be on wait list to get signed';
                break;
            case self::INVOICE_WAITING_CANCELATION:
                $status = 'The invoice is and should be on wait list to get canceled';
                break;
        }
        return $status;
    }
}