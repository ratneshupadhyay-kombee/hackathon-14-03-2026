<div>
    {{-- Edit Product Modal --}}
    @if($editingProductId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data x-on:keydown.escape.window="$wire.cancelEdit()">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-800">Edit Product</h3>
                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="editName"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Price ($) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input type="number" step="0.01" min="0" wire:model="editPrice"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 pl-7 pr-3 py-2.5 text-sm text-gray-900 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    @error('editPrice') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Stock Qty <span class="text-red-500">*</span></label>
                    <input type="number" min="0" wire:model="editStock"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    @error('editStock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="editIsActive" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="cancelEdit"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancel
                </button>
                <button wire:click="saveEdit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
                    <span wire:loading wire:target="saveEdit">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <x-powergrid::datagrid/>
</div>
