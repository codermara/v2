<?php

namespace NW\WebService\References\Operations\Notification;

class TsReturnOperation extends ReferencesOperation {
    const TYPE_NEW = 'new';
    const TYPE_CHANGE = 'change';

    public function doOperation() {
        $request_data = $this->getRequestData();

        if (!$this->validateRequestData($request_data)) {
            return $this->sendResponse('error', 'Invalid request data');
        }

        $product_id = $request_data['product_id'];
        $customer_id = $request_data['customer_id'];
        $return_type = $request_data['return_type'];
        $comments = $request_data['comments'];

        $product = $this->getProductById($product_id);
        $customer = $this->getCustomerById($customer_id);

        if (!$product || !$customer) {
            return $this->sendResponse('error', 'Product or customer not found');
        }

        if (!in_array($return_type, [self::TYPE_NEW, self::TYPE_CHANGE])) {
            return $this->sendResponse('error', 'Invalid return type');
        }

        $template = $this->generateNotificationTemplate($product, $customer, $return_type, $comments);
        $email_sent = $this->sendEmailNotification($customer['email'], $template);

        if ($email_sent) {
            return $this->sendResponse('success', 'Return operation completed');
        } else {
            return $this->sendResponse('error', 'Failed to send email notification');
        }
    }

    public function validateRequestData($request_data) {
        $required_fields = ['product_id', 'customer_id', 'return_type'];
        foreach ($required_fields as $field) {
            if (!isset($request_data[$field])) {
                return false;
            }
        }
        return true;
    }

    public function getProductById($product_id) {
        try {
            return Product::findOrFail($product_id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function getCustomerById($customer_id) {
        try {
            return Customer::findOrFail($customer_id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function generateNotificationTemplate($product, $customer, $return_type, $comments) {
        $template = 'Dear ' . $customer['name'] . ',<br><br>';
        if ($return_type == self::TYPE_NEW) {
            $template .= 'Thank you for returning the following product:<br><br>';
        } else {
            $template .= 'We have received your request to change the following product:<br><br>';
        }
        $template .= 'Product name: ' . $product['name'] . '<br>';
        $template .= 'Product price: ' . $product['price'] . '<br>';
        if ($comments) {
            $template .= 'Comments: ' . $comments . '<br>';
        }
        $template .= '<br>Best regards,<br>The TsReturn team';
        return $template;
    }

    public function sendEmailNotification($email, $template) {
        try {
            Mail::send([], [], function ($message) use ($email, $template) {
                $message->to($email)
                    ->subject('TsReturn Notification')
                    ->setBody($template, 'text/html');
            });
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}