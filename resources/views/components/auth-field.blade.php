@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => true, 'errorBag' => 'default'])
@php($message = $errors->getBag($errorBag)->first($name))
<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <div @class(['zaza-password-input' => $type === 'password']) @if ($type === 'password') x-data="{ visible: false }" @endif>
        <input {{ $attributes->class(['form-control', 'is-invalid' => $message]) }} id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            @if ($type === 'password') :type="visible ? 'text' : 'password'" @else value="{{ old($name, $value) }}" @endif
            @if ($message) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif @required($required)>
        @if ($type === 'password')
            <button type="button" class="zaza-password-toggle" x-cloak
                aria-controls="{{ $name }}" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                :aria-pressed="visible" @click="visible = !visible">
                <i class="bi" :class="visible ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
            </button>
        @endif
    </div>
    @if ($message)
        <p class="zaza-field-error small mt-1 mb-0" id="{{ $name }}-error">{{ $message }}</p>
    @endif
</div>
