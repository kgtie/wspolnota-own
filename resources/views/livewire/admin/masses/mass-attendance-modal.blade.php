<div>
    <div class="modal fade" id="massAttendanceModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lista obecności</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($mass)
                        <div class="alert alert-info">
                            <strong>{{ $mass->start_time->format('Y-m-d H:i') }}</strong><br>
                            {{ Str::limit($mass->intention, 50) }}
                        </div>

                        <h6>Zapisani użytkownicy ({{ count($attendees) }}):</h6>
                        <ul class="list-group list-group-flush">
                            @forelse($attendees as $user)
                                <li class="list-group-item d-flex align-items-center">
                                    @if($user->avatar)
                                        <img src="{{ Storage::disk('profiles')->url($user->avatar) }}" class="rounded-circle me-2"
                                            width="30" height="30">
                                    @else
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                            style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span>{{ $user->full_name ?? $user->name }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">
                                    Nikt jeszcze nie zapisał się na tę mszę.
                                </li>
                            @endforelse
                        </ul>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('livewire:init', () => {
            const modalEl = document.getElementById('massAttendanceModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            Livewire.on('open-attendance-modal', () => modal.show());
        });
    </script>
</div>