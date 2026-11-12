<div>
    <label for="attachments" class="mb-1 block text-sm font-medium">Attach files <span class="font-normal text-muted">(optional)</span></label>
    <input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.zip"
           class="block w-full text-sm text-muted file:mr-3 file:rounded-md file:border file:border-rule file:bg-raised file:px-3 file:py-1.5 file:text-ink">
    <p class="mt-1 text-xs text-muted">Up to 3 files, 5 MB each: screenshots, PDFs, text logs or ZIPs.</p>
    @if ($errors->has('attachments') || $errors->has('attachments.*'))
        <p class="mt-1 text-sm text-red-400">{{ $errors->first('attachments') ?: $errors->first('attachments.*') }}</p>
    @endif
</div>
