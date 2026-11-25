import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import type { Letterhead } from '@/types';

/**
 * Screen chrome shared by every printed document.
 *
 * The toolbar is rendered once, above the pages, and hidden at print time, so a
 * multi-page document does not repeat the buttons on every sheet.
 */
export function PrintDocument({ children }: { children: ReactNode }) {
    return (
        <div
            className="min-h-screen bg-muted/40 py-6 text-black print:bg-white print:py-0"
            style={{
                fontFamily:
                    "'Century Gothic', 'AppleGothic', 'Trebuchet MS', sans-serif",
                fontSize: '12px',
            }}
        >
            <div className="mx-auto mb-4 flex w-full max-w-[8.5in] items-center justify-between gap-2 font-sans print:hidden">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => window.history.back()}
                >
                    <ArrowLeft />
                    Back
                </Button>
            </div>

            {children}
        </div>
    );
}

/**
 * A single sheet of the document.
 *
 * Sized to 8.5in so the on-screen preview matches the paper the browser
 * paginates to. The paper colors are set here instead of on a wrapper so the
 * inline approval preview stays readable in dark mode too.
 */
export function PrintPage({ children }: { children: ReactNode }) {
    return (
        <article className="mx-auto mb-6 w-full max-w-[8.5in] break-after-page bg-white p-6 text-black shadow-sm last:break-after-auto print:mx-auto print:mb-0 print:max-w-none print:px-6 print:py-8 print:shadow-none">
            {children}
        </article>
    );
}

/**
 * The LGU letterhead every document is printed on.
 *
 * The municipal seal and the campaign logo are the official artwork shipped
 * with the app, so they print as-is beside the LGU's name.
 */
export function PrintLetterhead({ letterhead }: { letterhead: Letterhead }) {
    return (
        <div className="mb-4 text-center">
            <div className="flex items-center justify-center gap-3">
                <img
                    src="/LGU.png"
                    alt=""
                    className="h-[52px] w-[52px] object-contain"
                />

                <div className="text-center">
                    <p className="text-[12px] tracking-[0.03em]">
                        {letterhead.republic}
                    </p>
                    <p className="text-[12px]">{letterhead.province}</p>
                    <p className="mt-0.5 text-[12px] font-bold uppercase">
                        {letterhead.municipality}
                    </p>
                </div>

                <img
                    src="/Bagong%20Pilipinas%20logo.png"
                    alt="Bagong Pilipinas"
                    className="h-[52px] w-auto object-contain"
                />
            </div>
        </div>
    );
}

/**
 * A small centred caption, as used for a document title or section heading.
 */
export function PrintTitle({
    children,
    className = '',
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <p
            className={`text-center text-[12px] font-bold tracking-[0.06em] ${className}`}
        >
            {children}
        </p>
    );
}

/**
 * A signature line.
 *
 * The name prints pre-filled only when the system already knows it; otherwise
 * the line is left blank for the wet signature, because the form still has to
 * give the signatory somewhere to sign.
 *
 * The caption sits above the rule rather than below it, so a block with no
 * pre-filled name still shows what the signature is for.
 */
export function PrintSignature({
    label,
    name,
    title,
    note,
    className = '',
    align = 'center',
    line = true,
}: {
    label?: ReactNode;
    name?: string | null;
    title?: string | null;
    note?: ReactNode;
    className?: string;
    align?: 'left' | 'center';
    line?: boolean;
}) {
    return (
        <div
            className={`text-[12px] ${align === 'left' ? 'text-left' : 'text-center'} ${className}`}
        >
            {label && <p className="mb-9 text-[12px] font-bold">{label}</p>}
            <p
                className={`pt-1 font-bold uppercase ${line ? 'border-t border-black' : ''}`}
            >
                {name || ' '}
            </p>
            {title ? <p className="text-neutral-700">{title}</p> : null}
            {note ? <p className="text-neutral-700">{note}</p> : null}
        </div>
    );
}
