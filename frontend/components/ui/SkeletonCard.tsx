export default function SkeletonCard() {
  return (
    <div className="bg-surface-900 border border-white/5 rounded-2xl overflow-hidden relative">
      <div className="w-full h-48 bg-surface-800 animate-shimmer" />
      <div className="p-5 flex flex-col gap-3">
        <div className="h-5 bg-surface-800 rounded animate-shimmer w-3/4" />
        <div className="h-4 bg-surface-800 rounded animate-shimmer w-1/2 mt-2" />
        <div className="h-4 bg-surface-800 rounded animate-shimmer w-full" />
        <div className="h-10 bg-surface-800 rounded-xl animate-shimmer w-full mt-4" />
      </div>
    </div>
  );
}
