export default function AmbientGlow() {
  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden">
      {/* Orb 1: Red Top Right */}
      <div className="absolute -top-40 -right-40 h-[600px] w-[600px] rounded-full bg-red-600/10 blur-[150px] animate-float" />
      
      {/* Orb 2: Dark Red Bottom Left */}
      <div className="absolute -bottom-40 -left-40 h-[600px] w-[600px] rounded-full bg-brand-800/15 blur-[120px] animate-float-reverse" />
      
      {/* Orb 3: Black overlay for contrast */}
      <div className="absolute inset-0 bg-surface-950/40" />
    </div>
  );
}
