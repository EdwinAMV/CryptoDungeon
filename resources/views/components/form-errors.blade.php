@if ($errors->any())
    <div role="alert" class="mb-5 rounded-xl border border-red-800 bg-red-950 p-4 text-sm text-red-200">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
