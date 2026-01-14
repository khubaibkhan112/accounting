<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SanitizationTest extends TestCase
{
    public function test_it_sanitizes_string_inputs()
    {
        // Create an anonymous class extending BaseFormRequest to test the trait/method
        $request = new class extends BaseFormRequest {
            public function authorize() { return true; }
            public function rules() { return []; }
        };

        $input = [
            'name' => '  John Doe  ',
            'email' => ' John@Example.com ',
            'phone' => '(123) 456-7890',
            'description' => '<script>alert("xss")</script>Description',
        ];

        // We simulate the request lifecycle
        $request->merge($input);
        
        // Reflection to call protected method if needed, but validation triggering prepareForValidation is better
        // However, standard Request lifecycle in test might need help.
        // Let's call the public accessible method if we can, or just use the logic via a real validation call.
        
        // Easier: Inspect the code we saw earlier. "prepareForValidation" calls "sanitizeInput". 
        // We can manually call prepareForValidation since it's protected, via reflection or just by trusting the framework flow if we dispatched it.
        // Let's reflect for unit testing isolation.
        
        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('John Doe', $request->input('name'));
        $this->assertEquals('John@Example.com', $request->input('email')); // Standard Sanitization usually keeps case but trims? filter_var EMAIL usually keeps case.
        $this->assertEquals('(123) 456-7890', $request->input('phone')); // Regex in code allows + - ( ) space
        $this->assertEquals('alert(&quot;xss&quot;)Description', $request->input('description')); // htmlspecialchars encodes qutoes
    }
}
