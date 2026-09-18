@foreach (['basari' => 'ok', 'uyari' => 'warn', 'hata' => 'err', 'bilgi' => 'info'] as $anahtar => $stil)
    @if (session($anahtar))
        <div class="alert-ui alert-{{ $stil }} mb-3">
            <i class="bi bi-{{ ['ok' => 'check-circle', 'warn' => 'exclamation-triangle', 'err' => 'x-circle', 'info' => 'info-circle'][$stil] }}"></i>
            <div>{{ session($anahtar) }}</div>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert-ui alert-err mb-3">
        <i class="bi bi-x-circle"></i>
        <div>
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <strong>Lutfen su alanlari duzeltin:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $hata)
                        <li>{{ $hata }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
