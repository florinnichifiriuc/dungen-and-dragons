const encoder = new TextEncoder();
const decoder = new TextDecoder();
const SALT = 'dds-condition-storage';

type StoredPayload = {
    iv: string;
    data: string;
};

function bufferToBase64(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary);
}

function base64ToBuffer(value: string): ArrayBuffer {
    const binary = atob(value);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }

    return bytes.buffer;
}

async function deriveKey(): Promise<CryptoKey | null> {
    if (typeof window === 'undefined' || !window.crypto?.subtle) {
        return null;
    }

    const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content;
    const material = encoder.encode(`${window.location.origin}|${token ?? 'guest'}|${SALT}`);
    const hash = await window.crypto.subtle.digest('SHA-256', material);

    return window.crypto.subtle.importKey('raw', hash, 'AES-GCM', false, ['encrypt', 'decrypt']);
}

export async function setEncryptedItem<T>(key: string, value: T): Promise<void> {
    const cryptoKey = await deriveKey();

    if (!cryptoKey || typeof window === 'undefined') {
        return;
    }

    try {
        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        const payload = encoder.encode(JSON.stringify(value));
        const encrypted = await window.crypto.subtle.encrypt({ name: 'AES-GCM', iv }, cryptoKey, payload);
        const stored: StoredPayload = {
            iv: bufferToBase64(iv.buffer),
            data: bufferToBase64(encrypted),
        };

        window.localStorage.setItem(key, JSON.stringify(stored));
    } catch (error) {
        if (process.env.NODE_ENV !== 'production') {
            console.warn('Failed to persist encrypted item', error);
        }
    }
}

export async function getEncryptedItem<T>(key: string): Promise<T | null> {
    const cryptoKey = await deriveKey();

    if (!cryptoKey || typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.localStorage.getItem(key);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as StoredPayload;
        const ivBuffer = base64ToBuffer(parsed.iv);
        const dataBuffer = base64ToBuffer(parsed.data);
        const decrypted = await window.crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: new Uint8Array(ivBuffer) },
            cryptoKey,
            dataBuffer,
        );

        return JSON.parse(decoder.decode(decrypted)) as T;
    } catch (error) {
        if (process.env.NODE_ENV !== 'production') {
            console.warn('Failed to load encrypted item', error);
        }

        return null;
    }
}

export async function removeEncryptedItem(key: string): Promise<void> {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.removeItem(key);
}
