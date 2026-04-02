@php
    $isHidden = (bool) $comment->is_hidden;
    $isTrashed = $comment->trashed();
    $canReply = $this->canReplyToComment($comment);
    $canHide = $this->canHideComment($comment);
    $canRestoreVisibility = $this->canRestoreVisibility($comment);
    $canRestoreDeleted = $this->canRestoreDeleted($comment);
    $canDelete = $this->canDeleteComment($comment);
    $statusLabel = $this->commentStatusLabel($comment);
    $authorName = $this->commentAuthorName($comment);
    $postEditUrl = $this->postEditUrl($comment);
    $commentEditUrl = $this->commentEditUrl($comment);
    $replyModel = 'replyBodies.'.$comment->getKey();
    $children = $comment->children;
@endphp

@once
    <style>
        [x-cloak] {
            display: none !important;
        }

        .comment-thread {
            position: relative;
            padding: 1rem 1rem 1rem 1.1rem;
            border-radius: 1.45rem;
            border: 1px solid rgba(229, 231, 235, 0.96);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(249, 250, 251, 0.9) 100%);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
        }

        .comment-thread--depth-1,
        .comment-thread--depth-2 {
            margin-top: 0.85rem;
            margin-left: 1.4rem;
        }

        .comment-thread--depth-1 {
            border-left: 4px solid rgba(251, 191, 36, 0.28);
        }

        .comment-thread--depth-2 {
            border-left: 4px solid rgba(191, 219, 254, 0.6);
            background:
                linear-gradient(180deg, rgba(248, 250, 252, 0.98) 0%, rgba(255, 255, 255, 0.92) 100%);
        }

        .comment-thread--hidden {
            background:
                linear-gradient(180deg, rgba(255, 251, 235, 0.92) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .comment-thread--trashed {
            opacity: 0.88;
            background:
                repeating-linear-gradient(
                    -45deg,
                    rgba(248, 250, 252, 0.98),
                    rgba(248, 250, 252, 0.98) 12px,
                    rgba(241, 245, 249, 0.98) 12px,
                    rgba(241, 245, 249, 0.98) 24px
                );
        }

        .comment-thread__topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: start;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .comment-thread__identity {
            display: grid;
            gap: 0.4rem;
            min-width: 0;
        }

        .comment-thread__author {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.98rem;
            font-weight: 700;
            color: #111827;
        }

        .comment-thread__meta {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .comment-thread__post {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            min-width: 0;
            font-size: 0.86rem;
            font-weight: 600;
            color: #92400e;
            text-decoration: none;
        }

        .comment-thread__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            min-height: 1.8rem;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .comment-thread__badge[data-state="Widoczny"] {
            background: rgba(220, 252, 231, 0.95);
            color: #166534;
        }

        .comment-thread__badge[data-state="Ukryty"] {
            background: rgba(255, 237, 213, 0.95);
            color: #9a3412;
        }

        .comment-thread__badge[data-state="Usunięty"] {
            background: rgba(226, 232, 240, 0.95);
            color: #475569;
        }

        .comment-thread__body {
            margin-top: 0.95rem;
            color: #1f2937;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .comment-thread__body--placeholder {
            font-style: italic;
            color: #92400e;
        }

        .comment-thread__actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            opacity: 0;
            transform: translateY(3px);
            pointer-events: none;
            transition: opacity 140ms ease, transform 140ms ease;
        }

        .comment-thread:hover .comment-thread__actions,
        .comment-thread[data-replying="true"] .comment-thread__actions {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .comment-thread__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(209, 213, 219, 0.95);
            background: rgba(255, 255, 255, 0.98);
            color: #374151;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
        }

        .comment-thread__action[data-tone="reply"] {
            border-color: rgba(96, 165, 250, 0.45);
            color: #1d4ed8;
        }

        .comment-thread__action[data-tone="warn"] {
            border-color: rgba(251, 191, 36, 0.45);
            color: #92400e;
        }

        .comment-thread__action[data-tone="danger"] {
            border-color: rgba(248, 113, 113, 0.4);
            color: #b91c1c;
        }

        .comment-thread__reply {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.9rem;
            padding: 0.95rem;
            border-radius: 1.15rem;
            border: 1px solid rgba(219, 234, 254, 0.96);
            background: rgba(239, 246, 255, 0.9);
        }

        .comment-thread__reply-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #1d4ed8;
        }

        .comment-thread__reply-textarea {
            min-height: 7rem;
            width: 100%;
            padding: 0.85rem 0.95rem;
            border-radius: 1rem;
            border: 1px solid rgba(147, 197, 253, 0.95);
            background: #fff;
            color: #111827;
            resize: vertical;
        }

        .comment-thread__reply-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .comment-thread__reply-hint {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .comment-thread__reply-actions {
            display: inline-flex;
            gap: 0.55rem;
        }

        .comment-thread__reply-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            padding: 0.65rem 0.95rem;
            border-radius: 0.95rem;
            font-weight: 700;
        }

        .comment-thread__reply-button--primary {
            border: 0;
            background: #111827;
            color: #fff;
        }

        .comment-thread__reply-button--secondary {
            border: 1px solid rgba(209, 213, 219, 0.95);
            background: #fff;
            color: #374151;
        }

        .comment-thread__children {
            position: relative;
            margin-top: 0.4rem;
        }

        @media (max-width: 64rem) {
            .comment-thread__actions {
                opacity: 1;
                transform: none;
                pointer-events: auto;
            }

            .comment-thread--depth-1,
            .comment-thread--depth-2 {
                margin-left: 0.85rem;
            }

            .comment-thread__reply-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .comment-thread__reply-actions {
                width: 100%;
                justify-content: stretch;
            }

            .comment-thread__reply-actions > * {
                flex: 1 1 0;
            }
        }
    </style>
@endonce

<article
    class="comment-thread comment-thread--depth-{{ $depth }} {{ $isHidden ? 'comment-thread--hidden' : '' }} {{ $isTrashed ? 'comment-thread--trashed' : '' }}"
    x-data="{ replying: false }"
    x-bind:data-replying="replying"
    x-on:news-comment-reply-saved.window="if ($event.detail.commentId === {{ $comment->getKey() }}) replying = false"
>
    <div class="comment-thread__topbar">
        <div class="comment-thread__identity">
            <div class="comment-thread__author">
                <span>{{ $authorName }}</span>
                <span class="comment-thread__badge" data-state="{{ $statusLabel }}">{{ $statusLabel }}</span>
            </div>

            <div class="comment-thread__meta">
                <span>{{ $comment->created_at?->format('d.m.Y H:i') ?? '-' }}</span>
                <span>Poziom {{ $comment->depth + 1 }}</span>
                @if ($comment->hiddenBy)
                    <span>Ukryl: {{ $comment->hiddenBy->full_name ?: $comment->hiddenBy->name }}</span>
                @endif
            </div>

            @if ($depth === 0 && $postEditUrl)
                <a href="{{ $postEditUrl }}" class="comment-thread__post">
                    Wpis: {{ $comment->newsPost?->getDisplayTitle() }}
                </a>
            @endif
        </div>

        <div class="comment-thread__actions">
            @if ($canReply)
                <button type="button" class="comment-thread__action" data-tone="reply" x-on:click="replying = ! replying">
                    Odpowiedz
                </button>
            @endif

            <a href="{{ $commentEditUrl }}" class="comment-thread__action">Edytuj</a>

            @if ($canHide)
                <button
                    type="button"
                    class="comment-thread__action"
                    data-tone="warn"
                    x-on:click.prevent="if (confirm('Ukryć ten komentarz?')) $wire.hideComment({{ $comment->getKey() }})"
                >
                    Ukryj
                </button>
            @endif

            @if ($canRestoreVisibility)
                <button
                    type="button"
                    class="comment-thread__action"
                    x-on:click.prevent="if (confirm('Przywrócić widoczność tego komentarza?')) $wire.restoreVisibility({{ $comment->getKey() }})"
                >
                    Przywróć widoczność
                </button>
            @endif

            @if ($canRestoreDeleted)
                <button
                    type="button"
                    class="comment-thread__action"
                    x-on:click.prevent="if (confirm('Przywrócić usunięty komentarz?')) $wire.restoreDeletedComment({{ $comment->getKey() }})"
                >
                    Przywróć
                </button>
            @endif

            @if ($canDelete)
                <button
                    type="button"
                    class="comment-thread__action"
                    data-tone="danger"
                    x-on:click.prevent="if (confirm('Usunąć komentarz? Jeśli ma odpowiedzi, zostanie tylko ukryty.')) $wire.deleteComment({{ $comment->getKey() }})"
                >
                    Usuń
                </button>
            @endif
        </div>
    </div>

    <div class="comment-thread__body {{ ($isHidden || $isTrashed) ? 'comment-thread__body--placeholder' : '' }}">
        @if ($isTrashed)
            [Komentarz został usunięty]
        @elseif ($isHidden)
            [Komentarz został ukryty, ale pozostaje w drzewie odpowiedzi]
        @else
            {!! nl2br(e((string) $comment->body)) !!}
        @endif
    </div>

    @if ($canReply)
        <form
            class="comment-thread__reply"
            x-cloak
            x-show="replying"
            wire:submit.prevent="replyToComment({{ $comment->getKey() }})"
        >
            <label class="comment-thread__reply-label" for="reply-{{ $comment->getKey() }}">
                Odpowiedz na komentarz
            </label>

            <textarea
                id="reply-{{ $comment->getKey() }}"
                class="comment-thread__reply-textarea"
                rows="5"
                maxlength="2000"
                placeholder="Napisz odpowiedź..."
                wire:model.defer="{{ $replyModel }}"
            ></textarea>

            @error($replyModel)
                <div class="text-sm text-danger-600">{{ $message }}</div>
            @enderror

            <div class="comment-thread__reply-footer">
                <div class="comment-thread__reply-hint">
                    Odpowiedź zostanie dodana bez opuszczania listy komentarzy.
                </div>

                <div class="comment-thread__reply-actions">
                    <button type="button" class="comment-thread__reply-button comment-thread__reply-button--secondary" x-on:click="replying = false">
                        Anuluj
                    </button>
                    <button type="submit" class="comment-thread__reply-button comment-thread__reply-button--primary">
                        Opublikuj odpowiedź
                    </button>
                </div>
            </div>
        </form>
    @endif

    @if ($children->isNotEmpty())
        <div class="comment-thread__children">
            @foreach ($children as $child)
                @include('filament.support.news-comments.partials.thread-item', [
                    'comment' => $child,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </div>
    @endif
</article>
