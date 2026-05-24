<?php

namespace App\Validator\Constraints;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RecaptchaValidator extends ConstraintValidator
{
    private $requestStack;
    private $privateKey;

    public function __construct(RequestStack $requestStack, $privateKey)
    {
        $this->requestStack = $requestStack;
        $this->privateKey = $privateKey;
    }

    public function validate($value, Constraint $constraint)
    {
        $request = $this->requestStack->getCurrentRequest();
        $token = $request ? $request->request->get('g-recaptcha-response') : null;

        if (!$request || !$this->privateKey || !$token) {
            $this->context->buildViolation($constraint->message)->addViolation();
            return;
        }

        $response = $this->verify($token, $request->getClientIp());
        if (empty($response['success'])) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    private function verify($token, $ip)
    {
        $payload = http_build_query([
            'secret' => $this->privateKey,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if (false === $result) {
            return ['success' => false];
        }

        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : ['success' => false];
    }
}
