@props(['field', 'label', 'placeholder'])

<div class="booking-field booking-address">
    <label for="{{ $field }}">{{ $label }}</label>
    <div class="booking-address-control">
        <input
            class="husso-input"
            id="{{ $field }}"
            type="text"
            role="combobox"
            autocomplete="off"
            maxlength="255"
            aria-autocomplete="list"
            aria-controls="{{ $field }}-options"
            aria-describedby="{{ $field }}-message"
            aria-expanded="false"
            :aria-expanded="hasSuggestions('{{ $field }}').toString()"
            :aria-activedescendant="activeOption('{{ $field }}')"
            :aria-invalid="placeErrors.{{ $field }} ? 'true' : null"
            :value="addresses.{{ $field }}"
            @input="searchPlaces('{{ $field }}', $event.target.value)"
            @focus="focusAddress('{{ $field }}')"
            @blur="blurAddress('{{ $field }}')"
            @keydown="addressKeydown('{{ $field }}', $event)"
            placeholder="{{ $placeholder }}"
        >
        <ul id="{{ $field }}-options" class="booking-options" role="listbox" aria-label="{{ $label }}" x-show="hasSuggestions('{{ $field }}')" x-cloak>
            <template x-for="(suggestion, index) in suggestions.{{ $field }}" :key="suggestion.place_id">
                <li
                    role="option"
                    :id="'{{ $field }}-option-' + index"
                    :aria-selected="(activeIndex === index).toString()"
                    @mousedown.prevent
                    @click="selectPlace('{{ $field }}', suggestion)"
                    x-text="suggestion.text"
                ></li>
            </template>
        </ul>
    </div>
    <p
        id="{{ $field }}-message"
        class="booking-field-note"
        :class="{ 'sr-only': hasSuggestions('{{ $field }}') }"
        aria-live="polite"
        x-text="placeMessages.{{ $field }}"
    ></p>
    {{ $slot }}
</div>
