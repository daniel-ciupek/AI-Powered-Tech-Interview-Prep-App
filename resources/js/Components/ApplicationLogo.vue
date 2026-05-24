<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
        <defs>
            <linearGradient id="aurora" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#00f0ff"/>
                <stop offset="50%" stop-color="#7a2bff"/>
                <stop offset="100%" stop-color="#ff2bd6"/>
                <animateTransform attributeName="gradientTransform" type="rotate" values="0 .5 .5; 360 .5 .5" dur="14s" repeatCount="indefinite"/>
            </linearGradient>
            <linearGradient id="bubbleFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#0c0a24"/>
                <stop offset="100%" stop-color="#02010c"/>
            </linearGradient>
            <linearGradient id="capFill" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#00f0ff"/>
                <stop offset="50%" stop-color="#7a2bff"/>
                <stop offset="100%" stop-color="#ff2bd6"/>
                <animateTransform attributeName="gradientTransform" type="rotate" values="0 .5 .5; 360 .5 .5" dur="10s" repeatCount="indefinite"/>
            </linearGradient>
            <radialGradient id="halo" cx="50%" cy="60%" r="55%">
                <stop offset="0%" stop-color="#7a2bff" stop-opacity=".35"/>
                <stop offset="60%" stop-color="#ff2bd6" stop-opacity=".08"/>
                <stop offset="100%" stop-color="#00f0ff" stop-opacity="0"/>
            </radialGradient>
            <filter id="softGlow" x="-50%" y="-50%" width="200%" height="200%">
                <feGaussianBlur stdDeviation="0.35"/>
            </filter>
            <filter id="bigGlow" x="-100%" y="-100%" width="300%" height="300%">
                <feGaussianBlur stdDeviation="1.2"/>
            </filter>
        </defs>

        <ellipse cx="24" cy="28" rx="22" ry="20" fill="url(#halo)">
            <animate attributeName="opacity" values=".55;1;.55" dur="4.5s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            <animate attributeName="rx" values="21;23;21" dur="4.5s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
        </ellipse>

        <path d="M5 26.5 Q 5 20.5 11 20.5 L 37 20.5 Q 43 20.5 43 26.5 L 43 33.5 Q 43 39.5 37 39.5 L 22 39.5 L 16 44 L 18 39.5 L 11 39.5 Q 5 39.5 5 33.5 Z"
              fill="url(#bubbleFill)" stroke="url(#aurora)" stroke-width="1.3" stroke-linejoin="round" filter="url(#softGlow)"/>

        <circle r="1.1" fill="#fff" filter="url(#bigGlow)">
            <animateMotion dur="3.6s" repeatCount="indefinite"
                path="M5 26.5 Q 5 20.5 11 20.5 L 37 20.5 Q 43 20.5 43 26.5 L 43 33.5 Q 43 39.5 37 39.5 L 22 39.5 L 16 44 L 18 39.5 L 11 39.5 Q 5 39.5 5 33.5 Z"/>
            <animate attributeName="opacity" values="0;.9;.9;0" keyTimes="0;.08;.92;1" dur="3.6s" repeatCount="indefinite"/>
        </circle>

        <circle cx="16.5" cy="30" r="1.7" fill="#00f0ff">
            <animate attributeName="opacity" values=".35;1;.35" dur="2.2s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            <animate attributeName="r" values="1.4;2;1.4" dur="2.2s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
        </circle>
        <circle cx="22" cy="30" r="1.7" fill="#7a2bff">
            <animate attributeName="opacity" values=".35;1;.35" dur="2.2s" begin="0.28s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            <animate attributeName="r" values="1.4;2;1.4" dur="2.2s" begin="0.28s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
        </circle>
        <circle cx="27.5" cy="30" r="1.7" fill="#ff2bd6">
            <animate attributeName="opacity" values=".35;1;.35" dur="2.2s" begin="0.56s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            <animate attributeName="r" values="1.4;2;1.4" dur="2.2s" begin="0.56s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
        </circle>

        <g transform="translate(24 10)">
            <path d="M -13 5 L 0 0 L 13 5 L 0 10 Z" fill="url(#capFill)" filter="url(#softGlow)"/>
            <path d="M -10 6.5 L 0 10 L 10 6.5" stroke="#02010c" stroke-width="0.8" fill="none" opacity=".55" stroke-linejoin="round"/>
            <path d="M -12 5 L 0 0.8 L 12 5" stroke="#fff" stroke-width="0.4" fill="none" opacity=".4"/>
            <line x1="0" y1="10" x2="0" y2="13.5" stroke="#7a2bff" stroke-width="1.3" stroke-linecap="round"/>
            <g transform="translate(-9 2.5)">
                <path d="M 0 -1 L .35 -.35 L 1 0 L .35 .35 L 0 1 L -.35 .35 L -1 0 L -.35 -.35 Z" fill="#fff">
                    <animate attributeName="opacity" values=".3;1;.3" dur="2.6s" begin="0.4s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
                </path>
            </g>
            <line x1="9" y1="6" x2="9" y2="13" stroke="#b6ff3a" stroke-width="1.2" stroke-linecap="round">
                <animate attributeName="opacity" values=".7;1;.7" dur="2.4s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            </line>
            <circle cx="9" cy="14.5" r="2.2" fill="#b6ff3a" opacity=".25">
                <animate attributeName="r" values="1.8;2.8;1.8" dur="2.4s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
                <animate attributeName="opacity" values=".15;.4;.15" dur="2.4s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            </circle>
            <circle cx="9" cy="14.5" r="1.3" fill="#b6ff3a">
                <animate attributeName="cy" values="14.5;14.9;14.5" dur="2.4s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.6 1; 0.4 0 0.6 1"/>
            </circle>
            <circle cx="8.7" cy="14.2" r="0.4" fill="#fff"/>
        </g>
    </svg>
</template>
